<?

namespace Helper\Database\Pdo
{
	class Database
	{
		private $ident;
		private $cfg;


		public function __construct($db_ident)
		{
			$this->ident = $db_ident;
			$this->load_cfg();

			$this->link = \System\Database::get_db($db_ident);
		}


		private function load_cfg()
		{
			$this->cfg = cfg('database', 'list', $this->ident);

			if (!isset($this->cfg['dbms'])) {
				throw new \System\Error\Database('You must define dbms and host to use PDO database driver.');
			}

			if ($this->cfg['dbms'] != 'mysql') {
				throw new \System\Error\Database(sprintf('Database structure sync for dbms "%s" is not implemented in PDO.', $this->cfg['dbms']));
			}
		}


		public function get_table($name)
		{
			$drv = '\\Helper\\Database\\Pdo\\Table';
			return new $drv($this, $name);
		}


		public function query($query)
		{
			return \System\Database::query($query, $this->ident);
		}


		public function name()
		{
			return $this->cfg['database'];
		}


		public function mocked_driver()
		{
			return $this->cfg['dbms'];
		}
	}
}
