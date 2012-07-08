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
	}
}
