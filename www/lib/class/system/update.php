<?

namespace System
{
	class Update
	{
		const INFO_DIR = '/etc/current';
		//const UPDATE_URL = 'yacms.scourge.cz/packages';
		const UPDATE_URL = 'yacms.scourge.local/packages';
		const DIR = '/var/tmp/updates';

		private static $msg_title = "Aktualizace";
		private static $package_dir = '';

		private static function use_dir($dir)
		{
			if (is_dir($dir) && is_readable($dir)) {
				self::$package_dir = $dir;
				return true;
			} else {
				return message("error", self::$msg_title, _('Aktualizace se nezdařila - neplatný vstupní adresář'));
			}
		}


		private static function check_package($dir = NULL)
		{
			if (!$dir) $dir = self::$package_dir;

			if (file_exists($dir.'/.checksum')) {
				$bad = array();
				$sums = file($dir.'/.checksum', FILE_SKIP_EMPTY_LINES);
				foreach ($sums as $row) {
					$temp = array_filter(explode('  ', str_replace("\n", null, trim($row))));
					list($sum, $file) = $temp;
					$file = str_replace('./', null, $file);
					if ($file != 'changelog' && $sum != md5(file_get_contents($dir.'/'.$file))) {
						$bad[] = $file;
					}
				}
				return !!(empty($bad)) ? true:$bad;
			} else {
				return message("info", self::$msg_title, _('Chybí kontrolní soubor balíčku.'), true);
			}
			return false;
		}


		private static function make()
		{
			$bad = array();
			self::make_recursive(self::$package_dir, $bad);
			rename(self::$package_dir.'/.checksum', ROOT.self::INFO_DIR.'/checksum');
			rename(self::$package_dir.'/.changelog', ROOT.self::INFO_DIR.'/changelog');

			return !!(empty($bad)) ?
				message("success", _('Aktualizace'), _('Aktualizace byla úspěšně dokončena')):
				$bad;
		}


		private static function make_recursive($dir, &$bad)
		{
			$dp = opendir($dir);
			while ($f = readdir($dp)) {
				if (!in_array($f, array('.', '..'))) {
					if (is_dir($dir.'/'.$f)) {
						if (!is_dir($newdir = str_replace(self::$package_dir, ROOT, $dir).'/'.$f)) {
							mkdir($newdir);
						}
						self::make_recursive($dir.'/'.$f, $bad);
					} else {
						if (!copy($dir.'/'.$f, $nf = str_replace(self::$package_dir, ROOT, $dir).'/'.$f)) {
							$bad[] = str_replace(ROOT, NULL, $nf);
						}
					}
				}
			}
		}


		static function check_for_initial_data()
		{
			try {
				return !!Query::simple_count("user_perms", array('id_user_perm'));
			} catch (Exception $e) {
				return false;
			}
		}



		public static function get_update_list($branch = 'stable')
		{
			$url = 'http://' . self::UPDATE_URL . '/list.json.php';
			$list = json_decode(\Core\Request::get($url), true);
			return $list[$branch];
		}


		/* Return all files installed by YaCMS
		 * @return array Set of file paths
		 */
		public static function get_file_manifest()
		{
			$manifest = array();
			if (file_exists($f = ROOT.self::INFO_DIR.'/checksum')) {
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


		/* Download package into predefined space
		 * @return bool False on failure
		 */
		public static function download($branch, $version = '', $overwrite = false)
		{
			$p = ROOT.self::DIR;
			$done = false;
			
			if (!$version) {
				$updates = self::get_update_list($branch);
				$version = $updates['latest'];
			}

			$f = $p.'/'.self::get_update_name($branch, $version);
			!is_dir($p) && mkdir($p, 0770, true);
			$overwrite && file_exists($f) && unlink($f);

			$cont = \Core\Request::get('http://'.self::UPDATE_URL.'/'.$branch.'/yacms-core-'.$version.'.tar.bz2');

			if ($cont) {
				$done = file_put_contents($f, $cont);
			}

			return $done;
		}


		/* Use this package as new system
		 * @param string $branch
		 * @param string $version
		 */
		public static function apply($branch, $version)
		{
			$f = ROOT.self::DIR.'/'.self::get_update_name($branch, $version);
			if (file_exists($f)) {

				$ar = \Core\Archive::from('bz2', $f, true)->extract();
				self::use_dir(substr($ar->get_extract_path(), 0, strlen($ar->get_extract_path())-1));

				if (self::check_package()) {
					self::make();
				}

				\Core\File::clear_tmp();

			}

			return false;
		}


		/* Get name of local package
		 * @return string
		 */
		public static function get_update_name($branch, $version)
		{
			return 'yacms-'.$branch.'-'.$version.'.tar.bz2';
		}


		/* Get list of available package branches
		 * @return array set of branches
		 */
		public static function get_branches()
		{
			$url = 'http://' . self::UPDATE_URL . '/list.json.php';
			$list = json_decode(\Core\Request::get($url), true);
			$branches = array();
			
			foreach ($list as $branch => $data) {
				$data['count'] = count($data['list']);
				$branches[$branch] = $data;
			}

			return $branches;
		}
	}
}
