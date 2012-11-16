<?

namespace Database\Mysqli
{
	class Table
	{
		private $db;
		private $name;
		private $columns = array();
		private $comment = '';


		public function __construct(\Database\Mysqli\Database &$db, $name)
		{
			$this->name = $name;
			$this->db = $db;
			$this->load_columns();
		}


		public function exists($db_ident = null)
		{
			$db_name = $this->db()->name();
			$result = $this->db()->query(
				"SHOW TABLES WHERE
					Tables_in_".$db_name." = '".$this->name."'"
			)->fetch();

			$res = is_array($result) ? reset($result):$result;
			return $res == $this->name;
		}


		private function load_columns()
		{
			$result = $this->db()->query("SHOW COLUMNS FROM `".$this->name."`")->fetch_assoc();
			foreach ($result as $res) {
				$this->columns[$res['Field']] = new Column($this, $res['Field'], $res);
			}
		}


		public function get_columns()
		{
			return $this->columns;
		}


		public function get_column($name)
		{
			if (isset($this->columns[$name])) {
				return $this->columns[$name];
			} else throw new \DatabaseException(sprintf("Column '%s' does not exist in table '%s'", $name, $this->name));
		}


		public function add_column($name, $cfg)
		{
			$col = new Column($this, $name);
			if (!$col->exists()) {
				$col->set_cfg($cfg);
				return $col;
			} else throw new \DatabaseException(sprintf("Column '%s' already exists in table '%s'", $name, $this->name));
		}


		public function db()
		{
			return $this->db;
		}


		public function name()
		{
			return $this->name;
		}


		public function get_save_query()
		{
			$sq = array();

			foreach ($this->get_columns() as $col) {
				$sq[] = $col->get_save_query();
			}

			if ($this->exists()) {
				$query = "ALTER TABLE `".$this->name."`";
			} else {
				$query = "CREATE TABLE `".$this->name."`";
			}

			return $query."\n\t".implode(",\n\t", $sq).",\n\tCOMMENT='".$this->comment."'\n;";
		}


		public function save()
		{
			return $this->db()->query($this->get_save_query());
		}
	}
}
