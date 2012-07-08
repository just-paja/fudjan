<?

namespace System
{
	class Update
	{
		const DIR = '/var/tmp/updates';

		private static $msg_title = "Aktualizace";


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
	}
}
