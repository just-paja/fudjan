<?php

namespace System\Database\Driver
{
	interface Ifce
	{
		public function connect(array $config);
		public function disconnect();
		public function get_insert_id();
		public function begin();
		public function commit();
		public function rollback();
	}
}
