<?

namespace System
{
	abstract class Cache
	{
		static private $driver;
		static private $enabled;

		public static function init()
		{
			if (self::$enabled = Settings::get('cache', 'memory-cache-enabled'))
			{
				$driver = self::$driver = "Core\\System\\Cache\\".ucfirst(Settings::get('cache', 'memory-cache-driver'));
				try {
					$driver::setup();
				} catch (\Exception $e) {
					throw new \CacheException('Unusable type of cache \''.$driver.'\'. Check your app settings');
				}
			}
		}


		public static function get($path)
		{
			if (self::$enabled) {
				$driver = self::$driver;
				return $driver::get($path);
			}

			return false;
		}


		public static function fetch($path, &$var)
		{
			if (self::$enabled) {
				$driver = self::$driver;
				return $driver::fetch($path, $var);
			}

			return false;
		}


		public static function set($path, $value)
		{
			if (self::$enabled) {
				$driver = self::$driver;
				return $driver::set($path, $value);
			}

			return $value;
		}


		public static function release($path)
		{
			if (self::$enabled) {
				$driver = self::$driver;
				return $driver::release($path);
			}

			return null;
		}


		public static function flush()
		{
			if (self::$enabled) {
				$driver = self::$driver;
				return $driver::flush();
			}

			return null;
		}


		public static function get_driver()
		{
			return self::$driver;
		}
	}
}
