<?

namespace System
{
	abstract class Cache
	{
		const TTL_DEFAULT = 3600;

		static private $driver;
		static private $enabled;
		static private $ready = false;
		static private $ttl = self::TTL_DEFAULT;


		public static function init()
		{
			if (self::$enabled = cfg('cache', 'memory', 'enabled'))
			{
				if (class_exists(self::get_cfg_driver())) {
					self::$ready = self::setup_driver();
				} else throw new \CacheException('Cache driver does not exist. Check your app settings', cfg('cache', 'memory'));
			} else {
				self::$ready = true;
			}
		}


		public static function __callStatic($method, $args)
		{
			if (self::is_ready()) {
				if (method_exists(self::get_driver(), $method)) {
					return self::get_driver()->$method(def($args[0], null), def($args[1], null), def($args[2], null));
				} else throw new \CacheException(sprintf('Cache driver method does not exist: %s', $method));
			}
		}


		public static function fetch($path, &$var)
		{
			if (self::is_ready()) {
				return self::get_driver()->fetch($path, $var);
			}
		}


		public static function is_ready()
		{
			return self::$ready;
		}


		private static function setup_driver()
		{
			$drv_name = self::get_cfg_driver();
			self::$driver = new $drv_name();
			return true;
		}


		public static function get_cfg_driver()
		{
			return self::$driver = "\\System\\Cache\\Driver\\".ucfirst(cfg('cache', 'memory', 'driver'));
		}


		public static function get_driver()
		{
			return self::$driver;
		}
	}
}
