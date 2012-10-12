<?

namespace System
{
	class Settings
	{
		const CACHE_FILE        = '/var/cache/settings';
		const DIR_CONF_ALL      = '/etc';
		const DIR_CONF_DIST     = '/etc/conf.d';
		const DIR_CONF_STATIC   = '/etc/default/conf.d';
		const DIR_ROUTES_STATIC = '/etc/default/routes.d';
		const CONF_FILE_REGEXP  = '/^[a-z].*\.json$/i';

		private static $version_default = array(
			'pwf',
			'Purple Web Framework',
			'unknown version',
			'pwf',
			'master',
		);


		// Data
		private static $conf = array();

		// Environment
		private static $env = 'dev';

		// Internal modules and settings that will not be accessible from configurator
		private static $noconf = array(
			'own',
			'datatype_schema',
			'pages',
			'pass_shield',
			'core',
			'update_server'
		);

		static function init()
		{
			if (self::check_cache()) {
				self::load_cache();
			} else {
				self::reload();
				self::cache();
			}
		}


		public static function reload()
		{
			self::set_env();
			$dir = @opendir($p = ROOT.self::DIR_CONF_DIST.'/'.self::$env);
			if (!is_resource($dir)) {
				\System\Directory::create($p);
				self::reset();
				$dir = @opendir($p);
			}

			while ($file = readdir($dir)) {
				if (preg_match(self::CONF_FILE_REGEXP, $file) && !is_dir($p."/".$file)) {
					$d = explode(".", $file);
					array_pop($d);
					self::$conf[implode(null, $d)] = json_decode(file_get_contents($p."/".$file), true);
				}
			}

			if (!file_exists($p = ROOT.self::DIR_CONF_DIST.'/pages.json') && @!file_put_contents($p, '{}')) {
				throw new \InternalException(l('Couldn\'t create pages file. Please check your file system permissions on file "'.$p.'/".'));
			}

			if ($content = @file_get_contents($p)) {
				self::$conf['pages'] = json_decode($content, true);
			} else {
				throw new \InternalException('Couldn\'t find any pages. Please check JSON integrity of file "'.$p.'"');
			}

			$dir = opendir($p = ROOT.self::DIR_ROUTES_STATIC);
			while ($f = readdir($dir)) {
				if (strpos($f, ".") !== 0 && strpos($f, ".json")) {
					$key = substr($f, 0, strpos($f, "."));
					self::$conf['pages'][$key] = json_decode(file_get_contents($p.'/'.$f), true);

				}
			}

			$version_path = ROOT."/etc/current/core/yawf/version";

			if (file_exists($version_path)) {
				$cfg = explode("\n", file_get_contents($version_path, true));
			} else {
				\System\File::save_content($version_path, implode("\n", $cfg = self::$version_default));
			}



			self::$conf['own'] = array(
				'short_name' => $cfg[0],
				'name'       => $cfg[1],
				'version'    => $cfg[2],
				'package'    => $cfg[3],
				'branch'     => any($cfg[4]) ? $cfg[4]:'master',
			);


			ksort(self::$conf);
			Status::log('Settings', array("reloaded"), false);
		}


		public static function reset()
		{
			$p = ROOT.self::DIR_CONF_STATIC;
			$dir = opendir($p);

			while ($file = readdir($dir)) {
				if (is_file($np = ROOT.self::DIR_CONF_DIST.'/'.self::$env.'/'.$file)) {
					unlink($np);
				}
				if (is_file($p.'/'.$file)) {
					copy($p.'/'.$file, $np);
				}
			}
		}


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
			foreach ($conf as &$c) {
				unset($c['datatype_schema']);
			}

			if (!is_dir(dirname(self::get_cache_filename()))) {
				\System\Directory::create(dirname(self::get_cache_filename()), 0770);
			}

			$fp = file_put_contents(self::get_cache_filename(), serialize($conf));
			@chmod(self::get_cache_filename(), 0770);
			Status::log('Settings', array("written"), true);
		}


		/** Check cache file
		 * @return bool
		 */
		private static function check_cache()
		{
			$name = self::get_cache_filename();
			return is_file($name) && filemtime($name) > time() - 2;
		}


		/** Load cached data
		 */
		private static function load_cache()
		{
			self::$conf = unserialize(file_get_contents(self::get_cache_filename()));
			ksort(self::$conf);
			Status::log('Settings', array("loaded from cache"), true);
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
					throw new \InternalException(sprintf('There is no config on path \'%s\'', implode('/', $args)));
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

			if (!($action = @file_put_contents(
				ROOT.self::DIR_CONF_DIST.'/'.$env.'/'.$module.".json",
				\System\Json::json_humanize(json_encode(self::get($module)))
			))) {
				throw new \InternalException(sprintf(l('Failed to write settings. Please check your permissions on directory \'%s\''), ROOT.self::DIR_CONF_DIST));
			}

			Status::log('sys_notice', array("New config saved: ".self::DIR_CONF_DIST.'/'.$module.".json"), $action);
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
		 * @return void
		 */
		public static function set_env($env = null)
		{
			if (is_null($env)) {
				if (defined("YACMS_ENV")) {
					self::$env = YACMS_ENV;
				} elseif (file_exists($ef = ROOT.self::DIR_CONF_DIST.'/env')) {
						self::$env = trim(file_get_contents($ef));
				}
			} else {
				self::$env = $env;
			}

			if (!defined("YACMS_ENV")) {
				define("YACMS_ENV", self::$env);
			}
		}


		/** Does config environment exist
		 * @return bool
		 */
		public static function env_exists($env)
		{
			return is_dir(ROOT.self::DIR_CONF_DIST.'/'.$env);
		}


		public static function is_this_first_run()
		{
			return !file_exists($p = ROOT.self::DIR_CONF_ALL.'/install.lock');
		}
	}
}
