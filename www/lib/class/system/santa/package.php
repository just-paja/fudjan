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
		private $installed_meta = array();
		private $installed_version;


		/** Is any version of package installed?
		 * @return bool
		 */
		public function is_installed()
		{
			return file_exists($this->get_version_file());
		}


		/** Get installed version
		 * @return string
		 */
		public function get_installed_version()
		{
			if ($this->is_installed() && empty($this->installed_version)) {
				$meta = $this->get_installed_meta();
				$this->installed_version = $this->get_version($meta['origin'], $meta['version'], $meta['branch']);
			}

			return $this->installed_version;
		}


		public function get_installed_meta()
		{
			if ($this->is_installed() && empty($this->installed_meta)) {
				$this->installed_meta = \System\Json::read($this->get_version_file());
			}

			return $this->installed_meta;
		}


		/** Get path to temporary directory of package
		 * @return string
		 */
		public function get_meta_dir()
		{
			return ROOT.self::DIR_META.'/'.$this->get_full_name();
		}


		public function get_version_file()
		{
			return $this->get_meta_dir().'/version';
		}


		public function get_version($repo, $ver, $branch)
		{
			$v = new \System\Santa\Package\Version(array(
				"repo"    => $repo,
				"name"    => $ver,
				"branch"  => $branch,
				"package" => $this->get_full_name(),
			));

			$v->set_package($this);
			return $v;
		}


		/** Add exact version to available
		 * @param string $repo
		 * @param string $ver
		 * @param string $branch
		 * @return void
		 */
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

				$this->versions[$str]->set_package($this);
			}
		}


		/** Get full name of the package
		 * @return string
		 */
		public function get_full_name()
		{
			return $this->category.'/'.$this->name;
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
			if ($this->is_installed()) {

				foreach ($this->get_available() as $ver) {
					if ($ver->greater_than($this->get_installed_version())) {
						return true;
					}
				}
			}

			return false;
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
		 * @return System\Santa\Package\Version
		 */
		public function get_latest_version()
		{
			$versions = $this->get_available();
			$latest   = null;

			foreach ($versions as $version) {
				if (is_null($latest) || $latest->greater_than($version)) {
					$latest = $version;
				}
			}

			return $latest;
		}
	}
}
