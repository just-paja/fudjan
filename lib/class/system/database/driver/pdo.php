<?php

namespace System\Database\Driver
{
	class Pdo implements \System\Database\Driver\Ifce
	{
		const NO_ERROR = '00000';

		/** @var resource  Connection resource */
		private $connection;

		/** @var bool */
		private $autoFree = TRUE;

		/** @var bool  Is buffered (seekable and countable)? */
		private $buffered;

		private $config = array();
		private $db_selected = false;



		public function __construct()
		{
			if (!extension_loaded('pdo')) throw new \System\Error\Database("PHP extension 'pdo' is not loaded.");
		}


		/** Connects to a database.
		 * @return void
		 * @throws DatabaseException
		 */
		public function connect(array $config)
		{
			$this->config = $config;

			if (isset($config['resource']) && $config['resource'] instanceof PDO) {
				$this->connection = $config['resource'];

				if (!$this->is_connected()) {
					throw new \System\Error\Database('Could not connect to database "'.$config['database'].'" for following reasons.');
				}
			} else {

				if (!isset($config['charset'])) $config['charset'] = 'utf8';
				if (!isset($config['username'])) $config['username'] = null;
				if (!isset($config['password'])) $config['password'] = null;

				if (isset($config['dbms']) && isset($config['host'])) {
					$options = array();
					$options[] = sprintf('host=%s',    $config['host']);
					$options[] = sprintf('charset=%s', $config['charset']);
					$options[] = sprintf('dbname=%s',  $config['database']);

					if (isset($config['port'])) {
						$options[] = sprintf('port=%s', $config['port']);
					}

					$this->connection = new \PDO(sprintf('%s:%s', $config['dbms'], implode(';', $options)), $config['username'], $config['password']);
					$this->config['resource'] = &$this->connection;
				} else throw new \System\Error\Database('You must define dbms and host to use PDO database driver.');
			}

			if ($this->is_connected()) {
				if (isset($config['sqlmode'])) {
					$this->query("SET sql_mode='$config[sqlmode]'");
				}

				$this->query("SET time_zone='" . date('P') . "'");
				$this->buffered = empty($config['unbuffered']);
			}
		}


		/** Select database
		 * @param string $name
		 */
		private function select_db($name)
		{
			return $this->connect($this->config);
		}


		/** Disconnect from database.
		 * @return void
		 */
		public function disconnect()
		{
			$this->connection = null;
		}


		/** Execute SQL query
		 * @param  string      SQL statement.
		 * @return resource|NULL
		 */
		public function query($sql)
		{
			$res = $this->connection->query($sql);
			if ($this->connection->errorCode() != self::NO_ERROR) {
				$info = $this->connection->errorInfo();
				throw new \System\Error\Database($info[2], $this->connection->errorCode(), $sql);
			}

			return new \System\Database\Driver\PdoResult($res, false);
		}


		public function count($sql)
		{
			$result = $this->query($sql);
			return \System\Database\Query::first_val($result->fetch());
		}


		/** Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
		 * @return int|FALSE  int on success or FALSE on failure
		 */
		public function get_insert_id()
		{
			return $this->connection->lastInsertId();
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


		public function escape_string($value)
		{
			return $value;
		}


		public function create_database()
		{
			if ($this->is_connected()) {
				$this->query("CREATE DATABASE ".$this->config['database']);
			} else throw new \System\Error\Database(sprintf("Not connected to any server. Cannot create database %s", $this->config['database']));
		}


		public function is_connected()
		{
			return is_object($this->connection);
		}


		public function has_database()
		{
			return $this->db_selected;
		}


		public function is_ready()
		{
			return $this->is_connected() && $this->has_database();
		}
	}
}
