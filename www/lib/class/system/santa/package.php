<?

/** Santa package handling
 * @package santa
 */
namespace System\Santa
{
	/** Santa package handling
	 */
	class Package extends \System\Model\Attr
	{
		const DIR_TMP = '/var/tmp';
		const DIR_META = '/etc/current';
		const PKG_FORMAT = 'tar.bz2';
		const PATH_BIN = '/www/bin';

		/** Model attributes
		 * @param array
		 */
		static protected $attrs = array(
			"name"       => array('varchar'),
			"repo"       => array('varchar'),
			"category"   => array('varchar'),
			"project"    => array('varchar'),
			"desc"       => array('varchar'),
			"branch"     => array('varchar'),
			"homepage"   => array('varchar'),
		);


		private $versions = array();
		private $installed_version;


		public function is_installed()
		{
			return file_exists(ROOT.self::DIR_META.'/'.$this->category.'/'.$this->name.'/version');
		}


		public function is_downloaded()
		{
			return file_exists($this->get_file_path());
		}


		public function is_extracted()
		{
			return is_dir($dir_tmp = $this->get_tmp_dir()) && file_exists($dir_tmp.'/meta/checksum');
		}


		public function get_installed_version()
		{
			if (empty($this->installed_version)) {
				$info = explode("\n", \System\File::read(ROOT.self::DIR_META.'/'.$this->category.'/'.$this->name.'/version'));
				$this->installed_version = $info[5];
			}

			return $this->installed_version;
		}


		public function add_version($repo, $ver, $branch)
		{
			$str = $repo.'-'.$ver.'-'.$branch;

			if (empty($this->versions[$str])) {
				$this->versions[$str] = new \System\Santa\Package\Version(array(
					"repo"    => $repo,
					"name"    => $ver,
					"branch"  => $branch,
					"package" => $this->get_full_name(),
				));
			}
		}


		public function get_full_name()
		{
			return $this->category.'/'.$this->name;
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


		/** Get path to package file
		 * @return string
		 */
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
					if ($file != 'changelog' && $sum != md5(\System\File::read($dir.'/'.$file))) {
						$bad[] = $file;
					}
				}
				return !!(empty($bad)) ? true:$bad;
			} else throw new \System\Error\Santa(sprintf('Could not find checksum file for package "%s%".', $this->get_package_name()));
		}


		/** Download package into predefined space
		 * @return bool False on failure
		 */
		public function download()
		{
			if (!$this->downloaded) {
				$url = 'http://'.self::URL_SOURCE.'var/packages/'.$this->category.'/'.$this->name.'/'.$this->name.'-'.$this->version.'.tar.bz2';
				$data = \System\Offcom\Request::get($url);

				if ($data->ok()) {
					$this->downloaded = \System\File::put($this->get_file_path(), $data->content);
				} else throw new \System\Error\Connection(l('Fetching package'), sprintf(l('HTTP error %s '), $data->status));
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
			if ($this->is_installed()) {
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
			if (empty($this->versions)) {
				if ($data = \System\Santa::find($this->name, $this->category)) {
					foreach ($data as $repo=>$pkg) {
						foreach ($pkg['versions'] as $ver) {
							$this->add_version($repo, $ver['name'], $ver['branch']);
						}
					}
				}
			}

			return $this->versions;
		}


		/** Add version to list of available
		 * @param array $versions
		 */
		public function add_available(array $versions)
		{
			foreach ($versions as $ver) {
				$this->available[] = $ver['branch'].'/'.$ver['name'];
			}
		}


		/** Is there any update for this package
		 * @param bool $keep_branch Update in the same branch
		 * @return bool
		 */
		public function is_available_for_update()
		{
			foreach ($this->get_available() as $ver) {
				if (\System\Santa::greater_version_than($ver->name, $this->get_installed_version())) {
					return true;
				}
			}

			return false;
		}


		/** Install package
		 * @return bool False on failure
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

			return !!(empty($bad)) ? true:$bad;
		}


		/** Browse all dirs and copy files into install dir
		 * @param string $dir  Unpacked package directory
		 * @param string $root Local root to install package
		 * @param array  &$bad Reference to save bad files into
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
			return;
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
	}
}
