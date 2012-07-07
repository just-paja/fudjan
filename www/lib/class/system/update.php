<?

namespace System
{
	class Update
	{
		//const UPDATE_URL = 'yacms.scourge.cz/packages';
		const UPDATE_URL = 'yacms.scourge.local/packages';
		const DIR = '/var/tmp/updates';

		private static $msg_title = "Aktualizace";
		private static $package_dir = '';
		private static $package_list = array();
		private static $package_branch_list = array();


		/** Preset directory for update source
		 * @return bool false on failure
		 */
		private static function use_dir($dir)
		{
			if (is_dir($dir) && is_readable($dir)) {
				self::$package_dir = $dir;
				return true;
			} else {
				return message("error", self::$msg_title, _('Aktualizace se nezdařila - neplatný vstupní adresář'));
			}
		}


		/** Do an upgrade
		 * @return bool false on failure
		 */
		private static function install_package()
		{
			$bad = array();
			self::install_recursive(self::$package_dir, $bad);
			rename(self::$package_dir.'/.checksum', ROOT.self::INFO_DIR.'/checksum');
			rename(self::$package_dir.'/.changelog', ROOT.self::INFO_DIR.'/changelog');

			return !!(empty($bad)) ?
				message("success", _('Aktualizace'), _('Aktualizace byla úspěšně dokončena')):
				$bad;
		}


		/** Browse all dirs and copy files into install dir
		 * @return void
		 */
		private static function install_recursive($dir, &$bad)
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



		public static function get_update_list($branch = null)
		{
			$old = \System\Package::get_all_installed();
			$up = array();

			foreach ($old as $pkg) {
				if ($pkg->is_available_for_update(is_null($branch))) {
					$up[] = $pkg;
				}
			}

			return $up;
		}



		/** Get list of all versions of a package
		 * @return array List of versions
		 */
		public static function list_all($name)
		{
			$available = array();
			$plist = self::get_package_list();
			foreach ($plist as $branch_name => $branch) {
				foreach ($branch['list'] as $package) {
					if ($package['name'] == $name) {
						$available[] = $branch_name.'/'.$package['version'];
					}
				}
			}

			return $available;
		}
	}
}
