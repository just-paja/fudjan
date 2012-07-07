<?php

namespace System
{
	class Package extends Model\Attr
	{
		const DIR_TMP = '/var/tmp';
		const DIR_META = '/etc/current';
		const DIR_TMP_TREE = '/var/tmp/santa';
		const PKG_FORMAT = 'tar.bz2';
		const CACHE_MAX = 2592000;
		const URL_SOURCE = 'yacms.scourge.local/packages';

		static private $tree = array();
		static private $meta = array();
		static protected $attrs = array(
			"string"   => array('name', 'name_short', 'desc', 'branch', 'version', 'homepage'),
			"bool"     => array('installed', 'downloaded', 'extracted'),
		);

		private $available = array();


		public function __construct(array $dataray)
		{
			parent::__construct($dataray);
			$dir_tmp = $this->get_tmp_dir();
			$dir_meta = $this->get_meta_dir();

			$this->downloaded = file_exists(self::DIR_TMP.'/'.$this->get_file_name());
			$this->extracted = is_dir($dir_tmp) && file_exists($dir_tmp.'/checksum');

			if ($this->installed = is_dir($dir_meta) && file_exists($dir_meta.'/checksum')) {
				$cfg = explode("\n", file_get_contents($this->get_meta_dir().'/version', true));
				$this->update_attrs(array(
					'name_short' => $cfg[0],
					'desc'       => $cfg[1],
					'version'    => $cfg[2],
					'name'       => $cfg[3],
					'branch'     => any($cfg[4]) ? $cfg[4]:'stable',
				));
			}
		}


		/** Create package by passing path with metadata
		 * @param string
		 */
		public static function from_metadir($path)
		{
			if (is_dir($path) && file_exists($path.'/version')) {
				$cfg = explode("\n", file_get_contents($path.'/version', true));
				$pkg = new self(array(
					'name_short' => $cfg[0],
					'desc'       => $cfg[1],
					'version'    => $cfg[2],
					'name'       => $cfg[3],
					'branch'     => any($cfg[4]) ? $cfg[4]:'stable',
				));

				return $pkg;
			} else throw new \MissingFileException(sprintf(l('Cannot load package metadata from directory "%s"'), $path));
		}


		/** Get list of all installed packages
		 * @return array
		 */
		public static function get_all_installed()
		{
			$dir = opendir($p = ROOT.self::DIR_META);
			$packages = array();

			while ($file = readdir($dir)) {
				if (strpos($file, '.') !== 0 && is_dir($p.'/'.$file)) {
					if (file_exists($p.'/'.$file.'/checksum')) {
						$packages[] = self::from_metadir($p.'/'.$file);
					} else {
						$sdir = opendir($sp = $p.'/'.$file);
						while ($sfile = readdir($sdir)) {
							if (strpos($sfile, '.') !== 0 && is_dir($spd = $sp.'/'.$sfile) && file_exists($spd.'/checksum')) {
								$packages[] = self::from_metadir($spd);
							}
						}
					}
				}
			}

			closedir($dir);
			return $packages;
		}


		/** Get list of all installed files
		 * @return array
		 */
		public static function get_all_installed_files()
		{
			$packages = self::get_all_installed();
			$files = array();

			foreach ($packages as $pkg) {
				$files = array_merge($files, $pkg->get_file_manifest());
			}

			return $files;
		}


		/** Get whole tree of package list
		 * @return array
		 */
		private static function load_tree($force = false)
		{
			if (empty(self::$tree)) {
				if (file_exists($tp = ROOT.'/'.self::DIR_TMP_TREE.'/tree.json') && filectime($tp) > self::CACHE_MAX) {
					self::$tree = json_decode(file_get_contents($tp), true);
					if (empty(self::$tree)) {
						unlink($tp);
						self::$tree = self::load_tree();
					}
				} else {
					$tmp = json_decode(Request::get('http://'.self::URL_SOURCE.'/list.json.php'), true);
					self::$tree = $tmp['tree'];
					self::check_tree_dir();
					file_put_contents(ROOT.self::DIR_TMP_TREE.'/tree.json', json_encode(self::$tree));
					file_put_contents(
						ROOT.self::DIR_TMP_TREE.'/meta.json',
						Request::get('http://'.self::URL_SOURCE.'/meta.json.php')
					);
				}
			}
		}
		
		
		public static function greater_version_than($a, $b)
		{
			$a = array_map('intval', explode('.', $a));
			$b = array_map('intval', explode('.', $b));

			foreach ($a as $num) {
				if ($a > $b) {
					return true;
				} elseif ($a < $b) {
					return false;
				}
			}

			return 0;
		}


		/** Check if cache dir exists
		 * @return bool false if not
		 */
		private static function check_tree_dir()
		{
			return is_dir(ROOT.self::DIR_TMP_TREE) ?
				true:mkdir(ROOT.self::DIR_TMP_TREE, 0775, true);
		}


		/** Force reload package tree
		 */
		public static function reload_tree()
		{
			return self::load_tree(true);
		}


		/** Get whole package tree
		 * @return array
		 */
		public static function get_tree()
		{
			empty(self::$tree) && self::load_tree();
			return self::$tree;
		}


		/** Get list of available package branches
		 * @return array set of branches
		 */
		public static function get_branch_list()
		{
			empty(self::$tree) && self::load_tree();
			return array_keys(self::$tree);
		}


		/** Get path to temporary directory of package
		 * @return string
		 */
		private function get_tmp_dir()
		{
			return ROOT.self::DIR_TMP.'/'.$this->get_package_name();
		}


		/** Get path to temporary directory of package
		 * @return string
		 */
		private function get_meta_dir()
		{
			return ROOT.self::DIR_META.'/'.$this->name;
		}


		/** Get name and version of package
		 * @return string
		 */
		public function get_package_name()
		{
			return $this->name.'-'.$this->version.'-'.$this->branch;
		}


		/**
		 * Get file name of a package
		 * @return string
		 */
		public function get_file_name()
		{
			return $this->get_package_name().'.'.self::PKG_FORMAT;
		}


		/** Check package integrity. Browses all files and checks file md5 checksums
		 * @return bool fals on failure
		 */
		public function check()
		{
			$dir = $this->get_tmp_dir();

			if (file_exists($dir.'/checksum')) {
				$bad = array();
				$sums = file($dir.'/checksum', FILE_SKIP_EMPTY_LINES);
				foreach ($sums as $row) {
					$temp = array_filter(explode('  ', str_replace("\n", null, trim($row))));
					list($sum, $file) = $temp;
					$file = str_replace('./', null, $file);
					if ($file != 'changelog' && $sum != md5(file_get_contents($dir.'/'.$file))) {
						$bad[] = $file;
					}
				}
				return !!(empty($bad)) ? true:$bad;
			} else return message("info", self::$msg_title, l('Chybí kontrolní soubor balíčku.'), true);
		}


		/* Download package into predefined space
		 * @return bool False on failure
		 */
		public function download()
		{
			if (!$this->downloaded) {
				$f = ROOT.DIR_TMP.'/'.$this->get_file_name();
				$cont = \Core\Request::get('http://'.self::UPDATE_URL.'/'.$branch.'/yacms-core-'.$version.'.tar.bz2');

				if ($cont) {
					$this->downloaded = file_put_contents($f, $cont);
				}
			}

			return $this->downloaded;
		}


		/** Extract package into tmp dir
		 * @return bool false on failure
		 */
		public function extract()
		{
			if (!$this->extracted) {
				$f = ROOT.self::DIR_TMP.'/'.$this->get_file_name();
				if (file_exists($f)) {
					$ar = \Core\Archive::from('bz2', $f, true)->extract($this->get_tmp_dir());
					$this->extracted = true;
				}
			}

			return $this->extracted;
		}


		/** Return all files installed by YaCMS
		 * @return array Set of file paths
		 */
		public function get_file_manifest()
		{
			if ($this->installed) {
				$p = $this->get_meta_dir();
			} else {
				!$this->extracted && $this->extract();
				$p = $this->get_tmp_dir();
			}

			$manifest = array();
			if (file_exists($f = $p.'/checksum')) {
				$files = file($f);

				foreach ($files as $file) {
					list($checksum, $path) = explode('  ', trim($file));
					$manifest[] = array(
						"checksum" => $checksum,
						"path" => '/'.$path,
					);
				}
			}

			return $manifest;
		}


		/** Get list of all available versions
		 * @return array
		 */
		public function get_available()
		{
			if (empty($this->available)) {
				empty(self::$tree) && self::load_tree();

				foreach (self::$tree as $bname => $branch) {
					foreach ($branch as $cname => $category) {
						foreach ($category as $pkg) {
							if ($cname.'/'.$pkg['name'] == $this->name) {
								$this->add_available($bname, $pkg['versions']);
							}
						}
					}
				}
			}

			return $this->available;
		}


		public function add_available($branch, $versions)
		{
			foreach ($versions as $ver) {
				$this->available[] = $branch.'/'.$ver['version'];
			}
		}
		
		
		/** Is there any update for this package
		 * @return bool
		 */
		public function is_available_for_update($keep_branch = true)
		{
			foreach ($this->get_available() as $ver) {
				list($branch, $version) = explode('/', $ver);
				if (!$keep_branch || ($keep_branch && $branch == $this->branch)) {
					if (self::greater_version_than($version, $this->version)) {
						return true;
					}
				}
			}

			return false;
		}
	}
}
