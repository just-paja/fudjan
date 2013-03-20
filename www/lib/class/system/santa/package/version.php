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
				$p = $this->get_tmp_dir().self::DIR_META;
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
			$deprecated = array();

			if ($ver = $this->get_installed_version()) {
				$deprecated = $this->get_deprecated_files($ver);
			}

			self::install_recursive($tdir.'/data', $tdir.'/data', $bad);
			\System\Directory::check($this->pkg()->get_meta_dir());
			rename($tdir.'/meta/checksum',  $this->pkg()->get_meta_dir().'/checksum');
			rename($tdir.'/meta/changelog', $this->pkg()->get_meta_dir().'/changelog');

			\System\Json::put($this->pkg()->get_meta_dir().'/version', array(
				"name"    => $this->pkg()->name,
				"project" => $this->pkg()->project,
				"version" => $this->name,
				"branch"  => $this->branch,
				"origin"  => $this->repo,
			));

			foreach ($deprecated as $file) {
				if (file_exists($p = ROOT.$file)) {
					unlink($p);
				}
			}

			return !!(empty($bad)) ? true:$bad;
		}


		public function get_deprecated_files(self $ver)
		{
			$manifest_this  = $this->get_file_manifest();
			$manifest_other = $ver->get_file_manifest();
			$deprecated = array();

			foreach $manifest_other as $file_other) {
				foreach ($manifest_this as $file_this) {
					if ($file_this['path'] == $file_other['path']) {
						$found = true;
						break;
					}
				}

				if (!$found) {
					$deprecated[] = $file_other['path'];
				}
			}

			return $deprecated;
		}


		/** Browse all dirs and copy files into install dir
		 * @param string $dir  Unpacked package directory
		 * @param string $root Package root, that passes to the next function calls
		 * @param array  &$bad Reference to save bad files into
		 * @return void
		 */
		private static function install_recursive($dir, $root, &$bad)
		{
			$dp = opendir($dir);

			while ($f = readdir($dp)) {
				if (strpos($f, '.') !== 0) {
					if (is_dir($dir.'/'.$f)) {
						\System\Directory::check($newdir = str_replace($root, ROOT, $dir).'/'.$f);
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
			return self::greater_version_than($this->name, $version->name);
		}


		/** Compare two versions
		 * @param string $a
		 * @param string $b
		 * @return bool|null Null if equal
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

			return $this->is_extracted();
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


		public function get_file_conflicts(array $packages)
		{
			$result = array();

			foreach ($packages as $ver) {
				if ($conflict = $this->get_file_conflicts_for($ver)) {
					$result = array_merge($result, $conflict);
				}
			}

			return any($result) ? $result:false;
		}


		public function get_file_conflicts_for(self $ver)
		{
			$result = array();

			if ($this->pkg()->get_full_name() != $ver->pkg()->get_full_name()) {
				$files_this  = $this->get_file_manifest();
				$files_other = $ver->get_file_manifest();

				foreach ($files_this as $file_this) {
					foreach ($files_other as $file_other) {
						if ($file_this['path'] == $file_other['path']) {
							$result[] = array(
								"package" => $ver->full_name(),
								"file"    => $file_this['path'],
							);
						}
					}
				}
			}

			return any($result) ? $result:false;
		}


		public function clear_tmp()
		{
			\System\File::remove_directory($this->get_tmp_dir());
			unlink($this->get_package_path());
		}


		public function remove()
		{
			$manifest = $this->get_file_manifest();

			foreach ($manifest as $file) {
				if (file_exists($f = ROOT.$file['path'])) {
					unlink($f);
				}
			}

			\System\Directory::remove($this->pkg()->get_meta_dir());

			return $this->is_installed();
		}
	}
}
