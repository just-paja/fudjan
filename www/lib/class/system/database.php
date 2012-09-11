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
			$cfg = cfg('database');
			if (any($cfg['database'])) {
				self::connect(cfg('database'));
			} else {
				if (php_sapi_name() == 'cli') {
					exec(ROOT.'/bin/db --setup');
				} else {
					throw new \ConfigException(l('No database is set. Please run `bin/db --setup` to set up basic config or create config files manually'));
				}
			}
		}


		public static function connect(array $cfg)
		{
			$driver_name = 'System\\Database\\Driver\\'.ucfirst($cfg['driver']);
			$driver = &self::$instances[$cfg['database']];
			$driver = new $driver_name();
			$driver->connect($cfg);

			if (count(self::$instances) == 1) {
				self::$default_instance = &self::$instances[$cfg['database']];
			}
		}


		public static function query($query, $db_name = null)
		{
			if (($db = self::get_db($db_name)) !== null) {
				$res = $db->query($query);
				self::$queries ++;
				return $res;
			} else throw new \DatabaseException('Not connected to database "'.$db_name.'"');
		}


		public static function simple_insert($table, array $data, $add_times = true, $db_name = null)
		{
			if (($db = self::get_db($db_name)) !== null) {
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

				try {
					$res = $db->query($sql);
				} catch (\Exception $e) {
					throw new \DatabaseException(l('Could not insert data, query is below.'), $sql);
				}

				return $return_affected ? $db->get_affected_rows():$db->get_insert_id();

			} else throw new \DatabaseException('Not connected to database "'.$db_name.'"');
		}


		/** Perform a quick update
		 * @param string $table
		 * @param string $id_col
		 * @param array  $data
		 * @param bool   $add_times
		 */
		public static function simple_update($table, $id_col, $id, array $data, $add_times = true, $db_name = null)
		{
			if (($db = self::get_db($db_name)) !== null) {
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

				try {
					$result = $db->query($sql);
				} catch (\Exception $e) {
					throw new \DatabaseException(l('Could not update data, query is below.'), $sql);
				}

				return $result;
			} else throw new \DatabaseException('Not connected to database "'.$db_name.'"');
		}


		private static function get_default()
		{
			return empty(self::$default_instance) ? null:self::$default_instance;
		}


		private static function get_db($db_name = null)
		{
			return $db_name === null ?
				self::get_default():(isset(self::$instances[$db_name]) ? self::$instances[$db_name]:null);
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
					}
				} else {
					switch (gettype($value)) {
						case 'boolean': $value = $value ? 1:0; break;
						case 'integer': $value = intval($value); break;
						case 'double': case 'float': $value = str_replace(',', '.', floatval($value)); break;
						case 'NULL': $value = 'NULL'; break;
						default: $value = "'".self::get_db()->escape_string($value)."'";
					}

				}

				return $value;
			}
		}

		public static function get_insert_id($db_name = null)
		{
			return self::get_db($db_name)->get_insert_id();
		}


		public static function is_connected($db_name = null)
		{
			return self::get_db() !== null;
		}
	}
}
