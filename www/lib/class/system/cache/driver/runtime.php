<?

namespace System\Cache
{
	abstract class Runtime implements CacheInterface
	{
		private static $storage = array();


		public static function setup($ttl = 0, $storage = null, $port = null) {
			return null;
		}


		public static function fetch($path, &$var)
		{
			return $var = self::get($path);
		}


		public static function get($path)
		{
			return isset(self::$storage[members_to_path($path)]) ? self::$storage[members_to_path($path)]:null;
		}


		public static function set($path, $value)
		{
			return self::$storage[members_to_path($path)] = $value;
		}


		public static function release($path)
		{
			unset(self::$storage[members_to_path($path)]);
		}


		public static function flush()
		{
			self::$storage = array();
		}


		public static function set_ttl($ttl) { }
		public static function get_ttl() { }

		public static function set_storage($host, $port = null) { }
		public static function get_storage() { }
	}
}
