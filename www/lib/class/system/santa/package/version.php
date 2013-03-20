<?

namespace System\Santa\Package
{
	class Version extends \System\Model\Attr
	{
		const PKG_FORMAT = 'tar.bz2';
		const DIR_META   = '/meta';

		protected static $attrs = array(
			"name"    => array('varchar'),
			"repo"    => array('varchar'),
			"branch"  => array('varchar'),
			"package" => array('varchar'),
		);


		/** Is this package version installed?
		 * @return bool
		 */
		public function is_installed()
		{
			$meta = $this->pkg()->get_installed_meta();

			return
				$this->pkg()->is_installed()      &&
				(!isset($meta['origin']) || $meta['origin'] == $this->repo) &&
				$meta['branch']  == $this->branch &&
				$meta['version'] == $this->name;
		}


		/** Is this package version downloaded
		 * @return bool
		 */
		public function is_downloaded()
		{
			return file_exists($this->get_package_path());
		}


		/** Is this package version extracted
		 * @return bool
		 */
		public function is_extracted()
		{
			return is_dir($dir_tmp = $this->get_tmp_dir()) && file_exists($dir_tmp.'/meta/checksum');
		}


		public function set_package(\System\Santa\Package $pkg)
		{
			$this->pkg = $pkg;
		}


		public function pkg()
		{
			return $this->pkg;
		}


		public function full_name()
		{
			return $this->repo.'/'.$this->pkg()->get_full_name().'-'.$this->short_name();
		}


		public function name()
		{
			return $this->repo.'/'.$this->short_name();
		}


		public function package_name()
		{
			return $this->pkg()->name.'-'.$this->short_name().'.'.self::PKG_FORMAT;
		}


		public function full_package_name()
		{
			return ($this->repo ? $this->repo.'-':'').$this->package_name();
		}


		public function short_name()
		{
			return $this->name.($this->branch != 'stable' ? '-'.$this->branch:'');
		}



		/** Return all files installed by YaCMS
		 * @return array Set of file paths
		 */
		public function get_file_manifest()
		{
			if ($this->is_installed()) {
				$p = $this->pkg()->get_meta_dir();
			} else {
				!$this->is_extracted() && $this->extract();
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


		/** Is version instance greater than the other?
		 * @return bool
		 */
		public function greater_than(self $version)
		{
			return self::greater_version_than($version->name, $this->name);
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

			foreach ($a as $key=>$num) {
				if ($a[$key] > $b[$key]) {
					return true;
				} elseif ($a[$key] < $b[$key]) {
					return false;
				}
			}

			return null;
		}


		/** Get path to temporary directory of package
		 * @return string
		 */
		private function get_tmp_dir()
		{
			return ROOT.\System\Santa::DIR_TMP.'/'.$this->full_package_name().'-unpacked';
		}



		/** Extract package into tmp dir
		 * @return bool false on failure
		 */
		public function extract()
		{
			if (!$this->is_extracted()) {
				$this->download();
				$ar = \System\Archive::from('bz2', $this->get_package_path(), true)->extract($this->get_tmp_dir());
				$this->extracted = true;
			}

			return $this->extracted;
		}


		/** Download package into predefined space
		 * @return bool False on failure
		 */
		public function download()
		{
			if (!$this->is_downloaded()) {
				$data = \System\Offcom\Request::get($this->get_download_url());

				if ($data->ok()) {
					\System\File::put($this->get_package_path(), $data->content);
				} else throw new \System\Error\Connection(l('Fetching package'), sprintf(l('HTTP error %s '), $data->status));
			}

			return $this->is_downloaded();
		}


		public function get_download_url()
		{
			$host = str_replace('/sync/', '', cfg('santa', 'repositories', $this->repo));
			return $host.'/var/packages/'.$this->pkg()->category.'/'.$this->pkg()->name.'/'.$this->package_name();

		}


		/** Get file name of a package
		 * @return string
		 */
		public function get_package_path()
		{
			return ROOT.\System\Santa::DIR_TMP.'/'.$this->full_package_name();
		}


		public function get_meta()
		{
			if ($this->is_installed()) {
				return $this->pkg()->get_installed_meta();
			} else {
				$this->extract();
				$meta_path = $this->get_tmp_dir().self::DIR_META;
				$meta = array(
					"version"   => \System\Json::read($meta_path.'/version'),
					"checksum"  => \System\File::read($meta_path.'/checksum'),
					"changelog" => \System\File::read($meta_path.'/changelog'),
				);

				$meta['version']['origin'] = $this->repo ? $this->repo:'';
				return $meta;
			}
		}
	}
}
