<?php

namespace Helper\Database\Pdo
{
	class Database extends \Helper\Database\Mysqli\Database
	{
		public function get_table($name)
		{
			$drv = '\\Helper\\Database\\Pdo\\Table';
			return new $drv($this, $name);
		}
	}
}
