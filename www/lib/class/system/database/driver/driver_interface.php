<?

namespace System\Database\Driver
{
	interface DriverInterface
	{
		public function connect(array &$config);
		public function disconnect();
		public function get_info();
		public function get_affected_rows();
		public function get_insert_id();
		public function begin();
		public function commit();
		public function rollback();
		public function get_resource();
		public function escape($value, $type);
		public function escape_like($value, $pos);
		public function get_row_count();
		public function fetch();
	}
}
