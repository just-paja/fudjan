<?

namespace Database
{
	abstract class Structure
	{
		public static function get_default_config()
		{
			$dbs = cfg('database', 'list');
			foreach ($dbs as $ident=>$dbcfg) {
				if (any($dbcfg['is_yawf_home'])) {
					$dbcfg['ident'] = $ident;
					return $dbcfg;
				}
			}
		}


		public static function get_default_ident()
		{
			$dbcfg = self::get_default_config();
			return $dbcfg['ident'];
		}


		public static function get_driver_name($db_ident = null)
		{
			if (is_null($db_ident)) {
				$db_ident = self::get_default_ident();
			}

			if (\System\Database::exists($db_ident)) {
				return cfg('database', 'list', $db_ident, 'driver');
			} else throw new \DatabaseException(sprintf('Database %s does not exist', $db_ident));
		}


		public static function table_exists($name, $db_ident = null)
		{
			$drv = '\\Database\\'.ucfirst(self::get_driver_name($db_ident)).'\\Table';
			return $drv::exists($name, $db_ident);
		}


		public static function get_database($db_ident = null)
		{
			if (is_null($db_ident)) {
				$db_ident = self::get_default_ident();
			}

			$driver = '\\Database\\'.ucfirst(self::get_driver_name($db_ident)).'\\Database';
			return new $driver($db_ident);
		}


	}
}
