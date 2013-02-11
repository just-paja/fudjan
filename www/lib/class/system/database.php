<?

namespace System
{
	abstract class Database
	{
		const DIR_INITIAL_DATA = '/etc/database/data.d';
		const DIR_MIGRATIONS = '/etc/database/migrations.d';

		private static $instances = array();
		private static $default_instance;
		private static $queries = 0;

		public static function init()
		{
			$db_list = cfg('database', 'connect');

			if (any($db_list)) {
				foreach ($db_list as $db_ident) {
					$cfg = cfg('database', 'list', $db_ident);
					self::connect($cfg, $db_ident);
				}
			} else {
				if (php_sapi_name() == 'cli') {
					exec(ROOT.'/bin/db --setup');
				} else {
					throw new \System\Error\Config('No database is set.');
				}
			}
		}


		public static function connect(array $cfg, $ident = '')
		{
			!$ident && $ident = $cfg['database'];
			try {
				$default_ident = cfg('database', 'default');
			} catch (\System\Error $e) { $default_ident = $ident; }

			$driver_name = 'System\\Database\\Driver\\'.ucfirst($cfg['driver']);
			$driver = &self::$instances[$ident];
			$driver = new $driver_name();
			$driver->connect($cfg);

			if ((any($default_ident) && $ident == $default_ident) || count(self::$instances) === 1) {
				self::$default_instance = &self::$instances[$ident];
			}
		}


		public static function query($query, $db_ident = null)
		{
			if (($db = self::get_db($db_ident)) !== null) {
				$res = $db->query($query);
				self::$queries ++;
				return $res;
			} else throw new \System\Error\Database('Not connected to database "'.$db_ident.'"');
		}


		public static function count($query, $db_ident = null)
		{
			if (($db = self::get_db($db_ident)) !== null) {
				$res = $db->count($query);
				self::$queries ++;
				return $res;
			} else throw new \System\Error\Database('Not connected to database "'.$db_ident.'"');
		}


		public static function simple_insert($table, array $data, $add_times = true, $db_ident = null)
		{
			if (($db = self::get_db($db_ident)) !== null) {
				if ($add_times) {
					$data['created_at'] = new \DateTime();
					$data['updated_at'] = new \DateTime();
				}

				$sql = "INSERT INTO `".$table."` ";

				if ($return_affected = (isset($data[0]) && is_array($data[0]))) {

					// more rows per one query

				} else {
					$sql .= "SET ";
					$rows = array();

					foreach ($data as $column => $value) {
						$rows[] = "`".$column."`".' = '.self::escape($value);
					}

					$sql .= implode(',', $rows);
				}

				$res = $db->query($sql);
				return $return_affected ? $db->get_affected_rows():$db->get_insert_id();

			} else throw new \System\Error\Database('Not connected to database "'.$db_ident.'"');
		}


		/** Perform a quick update
		 * @param string $table
		 * @param string $id_col
		 * @param array  $data
		 * @param bool   $add_times
		 */
		public static function simple_update($table, $id_col, $id, array $data, $add_times = true, $db_ident = null)
		{
			if (($db = self::get_db($db_ident)) !== null) {
				if ($add_times) {
					$data['updated_at'] = new \DateTime();
				}

				$sql_data = array();
				$conds = array();

				if (is_array($id)) {
					$cond = "IN(".implode(',', array_map('intval', $id)).")";
				} else {
					$cond = "= ".$id;
				}

				foreach ($data as $col=>$data) {
					$sql_data[] = "`".$col."` = ".self::escape($data);
				}

				$sql = "UPDATE `".$table."` SET ".implode(',', $sql_data)." WHERE `".$id_col."` ".$cond;
				self::$queries ++;
				$result = '';

				self::get_db($db_ident)->query($sql);
				return $result;
			} else throw new \System\Error\Database('Not connected to database "'.$db_ident.'"');
		}


		private static function get_default()
		{
			return empty(self::$default_instance) ? null:self::$default_instance;
		}


		public static function get_db($db_ident = null)
		{
			return $db_ident === null ?
				self::get_default():(isset(self::$instances[$db_ident]) ? self::$instances[$db_ident]:null);
		}


		public static function escape(&$value)
		{
			if (is_array($value)) {
				array_walk($value, array('self', 'escape'));
			 } else {

				if (is_object($value)) {
					switch (get_class($value)) {
						case 'DateTime': $value = "'".format_date($value, 'sql')."'"; break;
						case 'System\Image': $value = "'".$value->to_json()."'"; break;
						case 'System\Gps': $value = $value->to_sql(); break;
					}
				} else {
					switch (gettype($value)) {
						case 'boolean': $value = $value ? 1:0; break;
						case 'integer': $value = self::num2db(intval($value)); break;
						case 'double': case 'float': $value = self::num2db(floatval($value)); break;
						case 'NULL': $value = 'NULL'; break;
						default: $value = "'".self::get_db()->escape_string($value)."'";
					}

				}

				return $value;
			}
		}


		public static function num2db($val)
		{
			$larr = localeconv();
			$search = array(
				$larr['decimal_point'],
				$larr['mon_decimal_point'],
				$larr['thousands_sep'],
				$larr['mon_thousands_sep'],
				$larr['currency_symbol'],
				$larr['int_curr_symbol']
			);
			$replace = array('.', '.', '', '', '', '');

			return str_replace($search, $replace, $val);
		}


		public static function get_insert_id($db_ident = null)
		{
			return self::get_db($db_ident)->get_insert_id();
		}


		public static function is_connected($db_ident = null)
		{
			$instance = self::get_db($db_ident);
			return $instance !== null && $instance->is_connected();
		}


		public static function is_ready($db_ident = null)
		{
			$instance = self::get_db($db_ident);
			return $instance !== null && $instance->is_ready();
		}


		public static function exists($db_ident)
		{
			$dblist = cfg('database', 'list');
			return isset($dblist[$db_ident]);
		}
	}
}
