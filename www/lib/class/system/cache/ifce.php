<?

namespace System\Cache
{
	interface Ifce
	{
		public static function store($path, $value, $ttl);
		public static function fetch($path, &$var);
		public static function get($path);
		public static function release($path);
		public static function flush();
	}
}
