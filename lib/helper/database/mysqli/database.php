<?php

namespace Helper\Database\Mysqli
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
		}


		public function get_table($name)
		{
			$drv = '\\Helper\\Database\\Mysqli\\Table';
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
	}
}
