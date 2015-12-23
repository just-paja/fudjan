<?php

namespace Helper\Database\Mysqli
{
	class Table
	{
		private $db;
		private $name;
		private $columns = array();
		private $comment = '';


		public function __construct(\Helper\Database\Mysqli\Database &$db, $name)
		{
			$this->name = $name;
			$this->db = $db;

			if ($this->exists()) {
				$this->load_columns();
			}
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
			if ($this->has_column($name)) {
				return $this->columns[$name];
			} else throw new \System\Error\Database(sprintf("Column '%s' does not exist in table '%s'", $name, $this->name));
		}


		public function has_column($name)
		{
			return isset($this->columns[$name]);
		}


		public function add_column($name, $cfg)
		{
			$col = new Column($this, $name);

			if (!$col->exists()) {
				$col->set_cfg($cfg);
				$this->columns[$name] = $col;
				return $col;
			} else throw new \System\Error\Database(sprintf("Column '%s' already exists in table '%s'", $name, $this->name));
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
				$query = "CREATE TABLE `".$this->name."` (";
			}

			$query .= "\n\t".implode(",\n\t", $sq);

			if ($this->exists()) {
				$query .= ",\n\tCOMMENT='".$this->comment."'\n;";
			} else {
				$query .= "\n\t) COMMENT='".$this->comment."'\n;";
			}

			return $query;
		}


		public function save()
		{
			return $this->db()->query($this->get_save_query());
		}


		public function add_attr(\Helper\Database\Attr $attr)
		{
			return $this->add_column($attr->name, $attr->get_data());
		}


		public function add_index($col)
		{
			$this->db()->query("ALTER TABLE `".$this->name."` ADD INDEX `".$col."` (`".$col."`);");
		}


		public function drop_index($col)
		{
			$this->db()->query("ALTER TABLE `".$this->name."` DROP INDEX `".$col."`;");
		}


		public function get_indexes()
		{
			$result = $this->db()->query("SHOW INDEXES FROM `".$this->name."`")->fetch_assoc();
			$indexes = array();

			foreach ($result as $res) {
				if ($res['Non_unique'] != 0) {
					$indexes[] = $res['Column_name'];
				}
			}

			return $indexes;
		}
	}
}
