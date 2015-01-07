<?

/** Settings
 * @package system
 * @subpackage core
 */
namespace System
{
	/** System settings
	 * @package system
	 * @prop $conf
	 * @prop $env
	 * @subpackage core
	 */
	class Settings
	{
		const DIR_CACHE              = '/var/cache/settings';
		const DIR_CONF_ALL           = '/etc';
		const DIR_CONF_DIST          = '/etc/conf.d';
		const DIR_CONF_GLOBAL        = '/etc/conf.d/global';
		const DIR_CONF_ROUTES        = '/etc/routes.d';
		const DIR_CONF_STATIC        = '/etc/default/conf.d';
		const DIR_CONF_ROUTES_STATIC = '/etc/default/routes.d';
		const DIR_CONF_ERRORS        = '/etc/errors.d';
		const FILE_VERSION           = '/etc/santa/core/pwf/version';
		const CONF_FILE_REGEXP       = '/^[a-z].*\.json$/i';

		/** Is the module initialized and ready */
		private static $ready = false;
		private static $loaded = array();

		const CACHE_TTL = 3600;

		/** Default version data to be used */
		private static $version_default = array(
			"name"    => "pwf",
			"project" => "Purple Web Framework",
			"version" => "unknown",
			"branch"  => "local",
			"origin"  => "local",
		);

		/** Data */
		private static $conf = array();

		/** Environment */
		private static $env = 'dev';


		/** Initialization
		 * @return void
		 */
		public static function init()
		{
			if (!self::$ready) {
				self::set_env();

				if (self::check_cache()) {
					self::load_cache();
				} else {
					self::reload();

					try {
						$cache = cfg('cache', 'settings');
					} catch(\System\Error $e) {
						$cache = false;
					}

					if ($cache) {
						self::cache();
					}
				}

				self::$ready = true;
			}
		}


		/** Reload from all config files
		 * @return void
		 */
		public static function reload()
		{
			self::check_env();

			\System\Directory::check(ROOT.self::DIR_CONF_GLOBAL);
			self::read(self::DIR_CONF_STATIC, true, self::$loaded, self::$conf);
			self::read(self::DIR_CONF_GLOBAL, true, self::$loaded, self::$conf);
			self::read(self::DIR_CONF_DIST.'/'.self::get_env(), true, self::$loaded, self::$conf);

			self::$conf['routes'] = self::read(self::DIR_CONF_ROUTES, true);

			$api = self::read(self::DIR_CONF_ROUTES_STATIC);

			foreach (self::$conf['domains'] as $domain=>$cfg) {
				if (!isset(self::$conf['routes'][$domain])) {
					self::$conf['routes'][$domain] = array();
				}
			}

			foreach (self::$conf['routes'] as &$list) {
				if (empty($list)) {
					$list = \System\Json::read(ROOT.self::DIR_CONF_ERRORS.'/no-routes.json');
				}

				foreach ($api as $url) {
					$list[] = $url;
				}
			}

			Status::report('info', "Settings reloaded");
		}


		public static function read($dir, $assoc_keys = false, &$files=array(), &$temp = array())
		{
			$dirs = \System\Composer::list_dirs($dir);
			return \System\Json::read_dist_all($dirs, $assoc_keys, $files, $temp);
		}


		/** Check if environment config directory exists and try to create it
		 * @return file
		 */
		public static function check_env()
		{
			if (!is_dir($p = BASE_DIR.self::DIR_CONF_DIST.'/'.self::$env)) {
				self::reset();
			}
		}


		/** Reset settings to default
		 * @return void
		 */
		public static function reset()
		{
			\System\Directory::check(BASE_DIR.self::DIR_CONF_DIST.'/'.self::$env);
		}


		/** Get name of cache file
		 * @return string
		 */
		private static function get_cache_filename()
		{
			return BASE_DIR.self::DIR_CACHE.DIRECTORY_SEPARATOR.self::$env.'.json';
		}


		/** Save config to one cached file
		 * @return void
		 */
		private static function cache()
		{
			$conf = self::$conf;

			if (!is_dir(dirname(self::get_cache_filename()))) {
				\System\Directory::create(dirname(self::get_cache_filename()), 0770);
			}

			$fp = \System\File::put(self::get_cache_filename(), json_encode($conf));
			@chmod(self::get_cache_filename(), 0770);
			Status::report('info', 'New settings saved');
		}


		/** Purge cache file
		 * @return bool
		 */
		public static function purge_cache()
		{
			return @unlink(self::get_cache_filename());
		}


		/** Check cache file
		 * @return bool
		 */
		private static function check_cache()
		{
			$name = self::get_cache_filename();
			return is_file($name) && (filemtime($name) > time() - self::CACHE_TTL);
		}


		/** Load cached data
		 */
		private static function load_cache()
		{
			self::$conf = unserialize(\System\File::read(self::get_cache_filename()));
			ksort(self::$conf);
		}


		/** Get configuration of a path
		 * @param mixed $path
		 */
		public static function &get($path)
		{
			$args = is_array($path) ? $path:func_get_args();

			$i = 0;
			$iter = &self::$conf;

			foreach ($args as $arg) {
				$i++;
				if (isset($iter[$arg])) {
					$iter = &$iter[$arg];
				} else {
					throw new \System\Error\Config(sprintf('There is no config on path \'%s\'', implode('/', $args)));
				}
			}

			return $iter;
		}


		/** Change config of a path
		 * @param array $path
		 * @param mixed $val
		 */
		public static function set(array $path, $val)
		{
			$iter = &self::$conf;

			foreach ($path as $arg) {
				if (!isset($iter[$arg])) {
					$iter[$arg] = array();
				}

				$iter = &$iter[$arg];
			}

			return $iter = $val;
		}


		/** Save module settings
		 * @param string $module
		 * @param string $env
		 */
		public static function save($module, $env = null)
		{
			is_null($env) && ($env = self::$env);
			$path = BASE_DIR.self::DIR_CONF_DIST.'/'.$env.'/'.$module.".json";
			$data = \System\Json::json_humanize(json_encode(self::get($module)));

			if (!($action = \System\File::put($path, $data))) {
				throw new \System\Error\Permissions(sprintf('Failed to write settings. Please check your permissions on directory \'%s\'', ROOT.self::DIR_CONF_DIST));
			}

			self::purge_cache();
			self::reload();
			return $action;
		}


		/** Return environment name
		 * @return string
		 */
		public static function get_env()
		{
			return self::$env;
		}


		public static function get_loaded_files()
		{
			return self::$loaded;
		}


		/** Force env or read it
		 * @param string $env Environment name
		 * @return void
		 */
		public static function set_env($env = null)
		{
			if (is_null($env)) {
				if (file_exists($ef = BASE_DIR.self::DIR_CONF_DIST.'/env')) {
						self::$env = trim(\System\File::read($ef));
				}
			} else {
				self::$env = $env;
			}

			if (!defined("YACMS_ENV")) {
				define("YACMS_ENV", self::$env);
			}
		}


		/** Does config environment exist
		 * @param string $env Environment name
		 * @return bool
		 */
		public static function env_exists($env)
		{
			return is_dir(BASE_DIR.self::DIR_CONF_DIST.'/'.$env);
		}


		/** Does config environment exist
		 * @param string $env Environment name
		 * @return bool
		 */
		public static function env_create($env)
		{
			return \System\Directory::check(BASE_DIR.self::DIR_CONF_DIST.'/'.$env);
		}
	}
}
