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
		const CACHE_FILE             = '/var/cache/settings';
		const DIR_CONF_ALL           = '/etc';
		const DIR_CONF_DIST          = '/etc/conf.d';
		const DIR_CONF_GLOBAL        = '/etc/conf.d/global';
		const DIR_CONF_ROUTES        = '/etc/routes.d';
		const DIR_CONF_STATIC        = '/etc/default/conf.d';
		const DIR_CONF_ROUTES_STATIC = '/etc/default/routes.d';
		const FILE_VERSION           = '/etc/santa/core/pwf/version';
		const CONF_FILE_REGEXP       = '/^[a-z].*\.json$/i';

		/** Is the module initialized and ready */
		private static $ready = false;

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
			self::set_env();
			self::check_env();

			$default = self::read(self::DIR_CONF_STATIC, true);
			$global  = array();
			$conf    = array();

			\System\Directory::check(ROOT.self::DIR_CONF_GLOBAL);
			\System\Json::read_dist(ROOT.self::DIR_CONF_DIST.'/'.self::$env, $conf, true);
			\System\Json::read_dist(ROOT.self::DIR_CONF_GLOBAL, $global, true);

			self::$conf = array_replace_recursive($default, $global, $conf);
			$pages_user = \System\Json::read($p = ROOT.self::DIR_CONF_DIST.'/pages.json', true);
			$pages_api  = array();

			self::$conf['routes'] = self::read(self::DIR_CONF_ROUTES, true);

			$api = self::read(self::DIR_CONF_ROUTES_STATIC);

			foreach (self::$conf['routes'] as &$list) {
				foreach ($api as $url) {
					$list[] = $url;
				}
			}

			Status::report('info', "Settings reloaded");
		}


		public static function read($dir, $assoc_keys = false)
		{
			$dirs = \System\Composer::list_dirs($dir);
			return \System\Json::read_dist_all($dirs, $assoc_keys);
		}


		/** Check if environment config directory exists and try to create it
		 * @return file
		 */
		public static function check_env()
		{
			if (!is_dir($p = ROOT.self::DIR_CONF_DIST.'/'.self::$env)) {
				self::reset();
			}
		}


		/** Reset settings to default
		 * @return void
		 */
		public static function reset()
		{
			\System\Directory::check(ROOT.self::DIR_CONF_DIST.'/'.self::$env);
		}


		/** Get name of cache file
		 * @return string
		 */
		private static function get_cache_filename()
		{
			return ROOT.self::CACHE_FILE.'-'.self::$env.'.serial';
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

			$fp = \System\File::put(self::get_cache_filename(), serialize($conf));
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
			$path = ROOT.self::DIR_CONF_DIST.'/'.$env.'/'.$module.".json";
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


		/** Force env or read it
		 * @param string $env Environment name
		 * @return void
		 */
		public static function set_env($env = null)
		{
			if (is_null($env)) {
				if (defined("YACMS_ENV")) {
					self::$env = YACMS_ENV;
				} elseif (file_exists($ef = ROOT.self::DIR_CONF_DIST.'/env')) {
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
			return is_dir(ROOT.self::DIR_CONF_DIST.'/'.$env);
		}


		/** Has site developer locked the installer?
		 * @return bool
		 */
		public static function is_this_first_run()
		{
			return !file_exists($p = ROOT.self::DIR_CONF_ALL.'/install.lock');
		}
	}
}
