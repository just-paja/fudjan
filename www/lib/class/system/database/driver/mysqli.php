<?

namespace System\Database\Driver
{
	class Mysqli implements DriverInterface
	{
		const ERROR_ACCESS_DENIED = 1045;
		const ERROR_DUPLICATE_ENTRY = 1062;
		const ERROR_DATA_TRUNCATED = 1265;

		/** @var resource  Connection resource */
		private $connection;

		/** @var bool */
		private $autoFree = TRUE;

		/** @var bool  Is buffered (seekable and countable)? */
		private $buffered;



		public function __construct()
		{
			if (!extension_loaded('mysqli')) throw new DependencyException("PHP extension 'mysqli' is not loaded.");
		}


		/**
		 * Connects to a database.
		 * @return void
		 * @throws DatabaseException
		 */
		public function connect(array &$config)
		{
			if (isset($config['resource'])) {
				$this->connection = $config['resource'];

			} else {

				if (!isset($config['charset'])) $config['charset'] = 'utf8';
				if (!isset($config['username'])) $config['username'] = ini_get('mysql.default_user');
				if (!isset($config['password'])) $config['password'] = ini_get('mysql.default_password');

				if (!isset($config['host'])) {
					$host = ini_get('mysql.default_host');
					if ($host) {
						$config['host'] = $host;
						$config['port'] = ini_get('mysql.default_port');
					} else {
						if (!isset($config['socket'])) $config['socket'] = ini_get('mysql.default_socket');
						$config['host'] = NULL;
					}
				}

				$host = empty($config['socket']) ?
					$config['host'] . (empty($config['port']) ? '' : ':' . $config['port']):
					$host = ':' . $config['socket'];

				$this->connection = empty($config['persistent']) ?
					@mysqli_connect($host, $config['username'], $config['password'], $config['database']):
					@mysqli_pconnect($host, $config['username'], $config['password'], $config['database']);
			}

			if (!is_object($this->connection)) {
				throw new \DatabaseException('Could not connect to database "'.$config['database'].'" for following reasons.', mysqli_error(), mysqli_errno());
			}

			if (isset($config['charset'])) {
				$ok = FALSE;
				if (function_exists('mysql_set_charset')) {
					// affects the character set used by mysql_real_escape_string() (was added in MySQL 5.0.7 and PHP 5.2.3)
					$ok = @mysqli_set_charset($config['charset'], $this->connection); // intentionally @
				}

				!$ok && $this->query("SET NAMES '$config[charset]'");
			}

			if (isset($config['sqlmode'])) {
				$this->query("SET sql_mode='$config[sqlmode]'");
			}

			$this->query("SET time_zone='" . date('P') . "'");
			$this->buffered = empty($config['unbuffered']);
		}


		/** Disconnect from database.
		 * @return void
		 */
		public function disconnect()
		{
			mysqli_close($this->connection);
		}


		/** Execute SQL query
		 * @param  string      SQL statement.
		 * @return resource|NULL
		 */
		public function query($sql)
		{
			$res = $this->connection->query($sql); 
			if ($this->connection->errno) {
				throw new \DatabaseException(mysqli_error($this->connection), mysqli_errno($this->connection), $sql);
			}

			return new \System\Database\Result($res);
		}


		/** Retrieves information about the most recently executed query.
		 * @return array
		 */
		public function get_info()
		{
			$res = array();
			preg_match_all('#(.+?): +(\d+) *#', mysqli_info($this->connection), $matches, PREG_SET_ORDER);

			if (!preg_last_error()) {
				foreach ($matches as $m) {
					$res[$m[1]] = (int) $m[2];
				}
			}

			return $res;
		}


		/** Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
		 * @return int|FALSE  number of rows or FALSE on error
		 */
		public function get_affected_rows()
		{
			return mysqli_affected_rows($this->connection);
		}


		/** Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
		 * @return int|FALSE  int on success or FALSE on failure
		 */
		public function get_insert_id()
		{
			return mysqli_insert_id($this->connection);
		}


		/** Begins a transaction (if supported).
		 * @param  string  optional savepoint name
		 * @return resource|NULL
		 */
		public function begin($savepoint = NULL)
		{
			return $this->query($savepoint ? "SAVEPOINT $savepoint" : 'START TRANSACTION');
		}


		/** Commits statements in a transaction.
		 * @param  string  optional savepoint name
		 * @return resource|NULL
		 */
		public function commit($savepoint = NULL)
		{
			return $this->query($savepoint ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT');
		}


		/** Rollback changes in a transaction.
		 * @param  string  optional savepoint name
		 * @return resource|NULL
		 */
		public function rollback($savepoint = NULL)
		{
			return $this->query($savepoint ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK');
		}


		/** Returns the connection resource.
		 * @return mixed
		 */
		public function get_resource()
		{
			return is_resource($this->connection) ? $this->connection : NULL;
		}


		/** Encodes data for use in a SQL statement.
		 * @param  mixed     value
		 * @param  string    type
		 * @return string    encoded value
		 * @throws InvalidArgumentException
		 */
		public function escape($value, $type)
		{
			switch ($type) {
				case \System\Database::TYPE_TEXT:
					if (!is_resource($this->connection)) {
						throw new \DatabaseException('Lost connection to server.');
					}
					return "'" . mysql_real_escape_string($value, $this->connection) . "'";

				case \System\Database::TYPE_BINARY:
					if (!is_resource($this->connection)) {
						throw new \DatabaseException('Lost connection to server.');
					}
					return "_binary'" . mysql_real_escape_string($value, $this->connection) . "'";

				case \System\Database::TYPE_IDENTIFIER: return '`' . str_replace('`', '``', $value) . '`';

				default: throw new ArgumentException('Unsupported type.');
			}
		}
		
		public function escape_string($value)
		{
			if (is_object($this->connection))
				return $this->connection->real_escape_string($value);

			throw new \DatabaseException('Lost connection to server.');
		}


		/** Encodes string for use in a LIKE statement.
		 * @param  string
		 * @param  int
		 * @return string
		 */
		public function escape_like($value, $pos)
		{
			$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\n\r\\'%_");
			return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
		}


		/** Automatically frees the resources allocated for this result set.
		 * @return void
		 */
		public function __destruct()
		{
			$this->autoFree && $this->get_result_resource() && $this->free();
		}


		/** Returns the result set resource.
		 * @return mysqli_result
		 */
		public function get_result_resource()
		{
			$this->autoFree = FALSE;
			return @$this->resultSet->type === NULL ? NULL : $this->resultSet;
		}


		/** Returns the number of rows in a result set.
		 * @return int
		 */
		public function get_row_count()
		{
			if (!$this->buffered) {
				throw new DevelopmentException('Row count is not available for unbuffered queries.');
			}

			return mysqli_num_rows($this->resultSet);
		}


		/** Fetches the row at current position and moves the internal cursor to the next position.
		 * @param  bool     TRUE for associative array, FALSE for numeric
		 * @return array    array on success, nonarray if no next record
		 */
		public function fetch($assoc = MYSQL_ASSOC)
		{
			return mysqli_fetch_array($this->resultSet, $assoc ? MYSQL_ASSOC : MYSQL_NUM);
		}


		/** Moves cursor position without fetching row.
		 * @param  int      the 0-based cursor pos to seek to
		 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
		 * @throws DatabaseException
		 */
		public function seek($row)
		{
			if (!$this->buffered) {
				throw new DevelopmentException('Cannot seek an unbuffered result set.');
			}

			return mysqli_data_seek($this->resultSet, $row);
		}


		/** Frees the resources allocated for this result set.
		 * @return void
		 */
		public function free()
		{
			mysqli_free_result($this->resultSet);
			$this->resultSet = NULL;
		}


		/** Returns metadata for all columns in a result set.
		 * @return array
		 */
		public function get_result_columns()
		{
			$count = mysqli_num_fields($this->resultSet);
			$columns = array();
			for ($i = 0; $i < $count; $i++) {
				$row = (array) mysqli_fetch_field($this->resultSet, $i);
				$columns[] = array(
					'name' => $row['name'],
					'table' => $row['table'],
					'fullname' => $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'],
					'nativetype' => strtoupper($row['type']),
					'vendor' => $row,
				);
			}
			return $columns;
		}
	}
}
