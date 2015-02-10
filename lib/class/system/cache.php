<?

namespace System
{
	/** Caching data using variety of drivers
	 * @uses \System\Cache\Driver
	 */
	abstract class Cache
	{
		const TTL_DEFAULT = 3600;
		const DIR_CACHE = '/var/cache';
		const DIR_STATIC = '/var/cache/static';

		static private $driver;
		static private $enabled;
		static private $ready = false;
		static private $ttl = self::TTL_DEFAULT;


		public static function init()
		{
			if (!self::$ready) {
				if (self::$enabled = cfg('cache', 'memory', 'enabled')) {
					if (class_exists(self::get_cfg_driver())) {
						self::$ready = self::setup_driver();
					} else throw new \System\Error\Config('Cache driver does not exist. Check your app settings', cfg('cache', 'memory'));
				} else {
					self::$ready = true;
				}
			}
		}


		public static function __callStatic($method, $args)
		{
			if (self::is_enabled()) {
				if (self::is_ready()) {
					if (method_exists(self::get_driver(), $method)) {
						return self::get_driver()->$method(def($args[0], null), def($args[1], null), def($args[2], null));
					} else throw new \System\Error\Wtf(sprintf('Cache driver method does not exist: %s', $method));
				}
			} else return null;
		}


		public static function fetch($path, &$var)
		{
			if (self::is_enabled()) {
				if (self::is_ready()) {
					return self::get_driver()->fetch($path, $var);
				}
			} else return $var = null;
		}


		public static function is_ready()
		{
			return self::$ready;
		}


		public static function is_enabled()
		{
			return self::$enabled;
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


		/**
		 * Builds static resource cache
		 */
		public static function build_static_for($lib = null)
		{
			if ($lib === null) {
				$base = BASE_DIR;
			} else {
				$base = BASE_DIR.\System\Composer::DIR_VENDOR.'/'.$lib;
			}

			$static = $base.'/share';
			$files  = \System\Directory::ls($static);
			$target = BASE_DIR.self::DIR_STATIC;

			foreach ($files as $file) {
				if (is_dir($static.DIRECTORY_SEPARATOR.$file)) {
					\System\Directory::copy($static.DIRECTORY_SEPARATOR.$file, $target.DIRECTORY_SEPARATOR.$file);
				} else {
					copy($static.DIRECTORY_SEPARATOR.$file, $target.DIRECTORY_SEPARATOR.$file);
				}
			}
		}


		public static function build_core()
		{
			\System\File::put(BASE_DIR.\System\Loader::FILE_CORE, \System\Loader::dump_core());
		}


		public static function build_locales()
		{
			$cfg = \System\Settings::get('locales', 'allowed');
			$loc = new \System\Locales();

			foreach ($cfg as $lang) {
				$loc->load_messages($lang);

				\System\File::put(BASE_DIR.\System\Locales::DIR_CACHE.'/'.$lang.'.json', json_encode($loc->get_messages($lang)));
			}
		}


		public static function clear()
		{
			$files = glob(BASE_DIR.self::DIR_CACHE.'/*');

			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				} else if (is_dir($file)) {
					\System\Directory::remove($file);
				}
			}
		}
	}
}
