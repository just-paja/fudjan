<?

namespace System
{
	abstract class Cache
	{
		const TTL_DEFAULT = 3600;

		static private $driver;
		static private $enabled;
		static private $ttl = self::TTL_DEFAULT;

		public static function init()
		{
			if (self::$enabled = cfg('cache', 'memory-cache-enabled'))
			{
				try {
					self::setup_driver();
				} catch (\Exception $e) {
					throw new \CacheException('Could not setup cache driver \''.$driver.'\'. Check your app settings', cfg('cache'));
				}
			}
		}


		public static function __callStatic($method, $args)
		{
			if (self::$enabled) {
				if (!self::ready()) {
					self::init();
				}

				if (method_exists(array(self::get_driver(), $method))) {
					return self::get_driver()->$method($args);
				}
			}
		}


		private static function setup_driver($name, array $cfg)
		{
			$drv_name = self::get_cfg_driver();
			self::$driver = new $drv_name();
		}


		public static function get_cfg_driver()
		{
			return self::$driver = "System\\Cache\\Driver\\".ucfirst(cfg('cache', 'memory-cache-driver'));
		}


		public static function get_driver()
		{
			return self::$driver;
		}
	}
}
