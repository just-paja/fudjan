<?

namespace Santa
{
	class Package extends \System\Model\Attr
	{
		const DIR_TMP = '/var/tmp';
		const DIR_META = '/etc/current';
		const DIR_TMP_TREE = '/var/tmp/santa';
		const PKG_FORMAT = 'tar.bz2';
		const CACHE_MAX = 2592000;
		const URL_SOURCE = 'purple_repo/';
		const PATH_BIN = '/www/bin';

		static private $tree = array();
		static private $meta = array();
		static protected $attrs = array(
			"string"   => array('name', 'category', 'name_short', 'desc', 'branch', 'version', 'homepage'),
			"bool"     => array('installed', 'downloaded', 'extracted'),
		);

		private $available = array();


		public function __construct(array $dataray)
		{
			parent::__construct($dataray);
			$dir_tmp = $this->get_tmp_dir();
			$dir_meta = $this->get_meta_dir();

			$this->downloaded = file_exists($this->get_file_path());
			$this->extracted = is_dir($dir_tmp) && file_exists($dir_tmp.'/meta/checksum');

			if ($this->is_installed()) {
				$cfg = explode("\n", file_get_contents($this->get_meta_dir().'/version', true));
				$this->update_attrs(array(
					'name_short' => $cfg[0],
					'desc'       => $cfg[1],
					'version'    => $cfg[2],
					'installed'  => $cfg[2] == $this->version,
					'name'       => $cfg[3],
					'branch'     => any($cfg[4]) ? $cfg[4]:'stable',
				));
			}
		}


		public function is_installed()
		{
			$dir_meta = $this->get_meta_dir();
			return is_dir($dir_meta) && file_exists($dir_meta.'/checksum');
		}


		/** Create package by passing path with metadata
		 * @param string
		 */
		public static function from_metadir($path)
		{
			if (is_dir($path) && file_exists($path.'/version')) {
				$category_tmp = explode('/', $path);
				array_pop($category_tmp);
				$cfg = explode("\n", file_get_contents($path.'/version', true));
				$pkg = new self(array(
					'category'   => array_pop($category_tmp),
					'name_short' => $cfg[0],
					'desc'       => $cfg[1],
					'version'    => $cfg[2],
					'name'       => $cfg[3],
					'branch'     => any($cfg[4]) ? $cfg[4]:'stable',
				));

				return $pkg;
			} else throw new \MissingFileException(sprintf(l('Cannot load package metadata from directory "%s"'), $path));
		}


		/** Create package using only its' name
		 * @param string $name
		 */
		public static function from_name($name)
		{
			self::load_tree();
			$category = '';

			if (strpos($name, '/') > 0) {
				list($category, $name) = explode('/', $name, 2);
			}

			foreach (self::$tree as $branch) {
				if ($category && any($branch[$name])) {
					return new self($branch[$name]);
				} else {
					foreach ($branch as $category) {
						if (any($category[$name])) {
							return new self($category[$name]);
						}
					}
				}
			}

			return false;
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
			if (empty(self::$tree) || $force) {
				if (!$force && file_exists($tp = ROOT.'/'.self::DIR_TMP_TREE.'/tree.json') && filectime($tp) > self::CACHE_MAX) {
					self::$tree = json_decode(file_get_contents($tp), true);
					if (empty(self::$tree)) {
						unlink($tp);
						self::load_tree();
					}
				} else {
					$data = \System\Offcom\Request::get('http://'.self::URL_SOURCE.'/package-sync/');
					if ($data->ok()) {
						$tmp = json_decode($data->content, true);
						self::$tree = $tmp['tree'];
						self::check_tree_dir();
						file_put_contents(ROOT.self::DIR_TMP_TREE.'/tree.json', json_encode(self::$tree));
					} else throw new \InternalException(l('Fetching recent tree data failed'), sprintf(l('HTTP error %s '), $data->status));
				}
			}
		}


		/** Compare two versions
		 * @param string $a
		 * @param string $b
		 * @return bool|int 0 if equal
		 */
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
			self::load_tree();
			return array_keys(self::$tree);
		}


		public static function exists($name)
		{
			self::load_tree();
			$category = '';

			if (strpos($name, '/') > 0) {
				list($category, $name) = explode('/', $name, 2);
			}

			foreach (self::$tree as $branch) {
				if ($category && any($branch[$name])) {
					return true;
				} else {
					foreach ($branch as $category) {
						if (any($category[$name])) {
							return true;
						}
					}
				}
			}
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
			return ROOT.self::DIR_META.'/'.$this->get_full_name();
		}


		/** Get name and version of package
		 * @return string
		 */
		public function get_package_name()
		{
			return str_replace('/', '_', $this->get_full_name()).'-'.$this->version;
		}


		/** Get file name of a package
		 * @return string
		 */
		public function get_file_name()
		{
			return $this->get_package_name().'.'.self::PKG_FORMAT;
		}


		public function get_file_path()
		{
			return ROOT.self::DIR_TMP.'/'.$this->get_file_name();
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
				$url = 'http://'.self::URL_SOURCE.'var/packages/'.$this->category.'/'.$this->name.'/'.$this->name.'-'.$this->version.'.tar.bz2';
				$data = \System\Offcom\Request::get($url);

				if ($data->ok()) {
					$this->downloaded = \System\File::save_content($this->get_file_path(), $data->content);
				} else throw new \InternalException(l('Fetching package'), sprintf(l('HTTP error %s '), $data->status));
			}

			return $this->downloaded;
		}


		/** Extract package into tmp dir
		 * @return bool false on failure
		 */
		public function extract()
		{
			if (!$this->extracted) {
				$this->download();
				$ar = \System\Archive::from('bz2', $this->get_file_path(), true)->extract($this->get_tmp_dir());
				$this->extracted = true;
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

				foreach (self::$tree as $cname => $category) {
					foreach ($category as $pkg_name => $pkg) {
						if ($pkg_name == $this->name) {
							$this->add_available($pkg['versions']);
						}
					}
				}
			}

			return $this->available;
		}


		/** Add version to list of available
		 * @param string branch
		 * @param string version
		 */
		public function add_available($versions)
		{
			foreach ($versions as $ver) {
				$this->available[] = $ver['branch'].'/'.$ver['name'];
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


		/** Install package
		 */
		public function install()
		{
			$this->extract();
			$bad = array();
			$tdir = $this->get_tmp_dir();
			self::install_recursive($tdir.'/data', $tdir.'/data', $bad);

			@mkdir(ROOT.self::DIR_META.'/'.$this->name, 0777, true);
			rename($tdir.'/meta/checksum',  ROOT.self::DIR_META.'/'.$this->name.'/checksum');
			rename($tdir.'/meta/changelog', ROOT.self::DIR_META.'/'.$this->name.'/changelog');
			rename($tdir.'/meta/version',   ROOT.self::DIR_META.'/'.$this->name.'/version');

			if ($this->name == 'core/yawf') {
				copy(ROOT.self::DIR_META.'/'.$this->name.'/version', ROOT.self::DIR_META.'/version');
			}

			return !!(empty($bad)) ?
				message("success", sprintf(l('Install %s'), $this->name), l('Installation was successful')):
				$bad;
		}


		/** Browse all dirs and copy files into install dir
		 * @return void
		 */
		private static function install_recursive($dir, $root, &$bad)
		{
			$dp = opendir($dir);
			while ($f = readdir($dp)) {
				if (!in_array($f, array('.', '..'))) {
					if (is_dir($dir.'/'.$f)) {
						if (!is_dir($newdir = str_replace($root, ROOT, $dir).'/'.$f)) {
							mkdir($newdir, 0777, true);
						}
						self::install_recursive($dir.'/'.$f, $root, $bad);
					} else {
						if (!copy($dir.'/'.$f, $nf = str_replace($root, ROOT, $dir).'/'.$f)) {
							$bad[] = str_replace(ROOT, NULL, $nf);
						}
					}
				}
			}
		}


		/** Check if package files don't conflict any other
		 * @return array Conflict
		 */
		public function check_files()
		{
			$installed = self::get_all_installed();
			$my_files = $this->get_file_manifest();
			$blocks = array();

			foreach ($installed as $pkg) {
				if ($pkg->name != $this->name) {
					$current_files = $pkg->get_file_manifest();
					foreach ($my_files as $mf) {
						foreach ($current_files as $cf) {
							if ($mf['path'] == $cf['path']) {
								$blocks[] = array(
									"path" => $mf['path'],
									"old"  => $cf['checksum'],
									"new"  => $cf['checksum'],
									"package" => $pkg->name.'-'.$pkg->version,
								);
							}
						}
					}
				}
			}

			return $blocks;
		}


		/** Get latest version of package
		 * @return string
		 */
		public function latest_version()
		{
			$versions = $this->get_available();
			$latest = '';

			foreach ($versions as $version) {
				list($branch, $ver) = explode('/', $version);
				if (self::greater_version_than($version, $latest)) {
					$latest = $version;
				}
			}

			return $latest;
		}


		/** Get list of available updates
		 * @param  bool  $keep_branch Should updates keep branch that is installed?
		 * @return array
		 */
		public static function get_update_list($branch = null)
		{
			$old = self::get_all_installed();
			$up = array();

			foreach ($old as $pkg) {
				if ($pkg->is_available_for_update(is_null($branch))) {
					$up[] = $pkg;
				}
			}

			return $up;
		}


		public function get_full_name()
		{
			return $this->category.'/'.$this->name;
		}

	}
}
