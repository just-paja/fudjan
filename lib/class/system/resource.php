<?

namespace System
{
	abstract class Resource
	{
		const TYPE_SCRIPTS = 'scripts';
		const TYPE_STYLES  = 'styles';
		const TYPE_THUMB   = 'thumb';

		const SYMBOL_NOESS = 'noess';
		const DIR_TMP      = '/var/cache';
		const URL_BASE     = '/^\/share\/resource\//';
		const URL_MATCH    = '/([a-zA-Z]+)\/([0-9a-zA-Z\.\-;_=:]*)$/';

		const SCRIPTS_DIR = '/share';
		const SCRIPTS_STRING_NOT_FOUND = 'console.log("Jaffascript module not found: %s");';

		const STYLES_DIR = '/share';
		const STYLES_STRING_NOT_FOUND = '/* Style module not found: %s */';

		const KEY_SUM              = 'sum';
		const KEY_TYPE             = 'type';
		const KEY_FOUND            = 'found';
		const KEY_MISSING          = 'missing';
		const KEY_DIR_FILES        = 'modules';
		const KEY_DIR_CONTENT      = 'content';
		const KEY_STRING_NOT_FOUND = 'not_found_string';
		const KEY_POSTFIXES        = 'postfixes';
		const KEY_CALLBACK_RESOLVE = 'resolve';

		const MAX_AGE = 86400;


		private static $serial = null;

		private static $types = array(
			self::TYPE_SCRIPTS => array(
				self::KEY_DIR_FILES        => self::SCRIPTS_DIR,
				self::KEY_STRING_NOT_FOUND => self::SCRIPTS_STRING_NOT_FOUND,
				self::KEY_DIR_CONTENT      => 'text/javascript',
				self::KEY_POSTFIXES        => array('js'),
			),
			self::TYPE_STYLES => array(
				self::KEY_DIR_FILES        => self::STYLES_DIR,
				self::KEY_STRING_NOT_FOUND => self::STYLES_STRING_NOT_FOUND,
				self::KEY_DIR_CONTENT      => 'text/css',
				self::KEY_POSTFIXES        => array('css'),
			),
			self::TYPE_THUMB => array(
				self::KEY_DIR_FILES        => \System\Cache\Thumb::DIR,
				self::KEY_CALLBACK_RESOLVE => array('\System\Image', 'request_thumb'),
			)
		);


		/** Serve request
		 * @return void
		 */
		public static function request(\System\Http\Request $request)
		{
			preg_match(self::URL_MATCH, $request->path, $matches);

			if (any($matches) && isset($matches[1]) && isset($matches[2])) {
				$type = $matches[1];
				$info = self::get_type_info($type);
				$modules = self::get_module_list($info['type'], $matches[2]);

				if (any($modules)) {
					$files   = self::file_list($info[self::KEY_TYPE], $modules);
					$content = self::get_content($info, $files);

					self::send_header($info['type'], strlen($content));
					echo $content;
				} else if (isset($info[self::KEY_CALLBACK_RESOLVE])) {
					return call_user_func_array($info[self::KEY_CALLBACK_RESOLVE], array($request, $info));
				} else throw new \System\Error\NotFound();
			} else throw new \System\Error\NotFound();
		}


		/** Get content of requested files and minify it
		 * @param array $info  Type info
		 * @param list  $files List of files
		 * @return string
		 */
		private static function get_content(array $info, array $files)
		{
			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if (!$debug && file_exists($f = self::get_cache_path($info, $files[self::KEY_SUM]))) {
				$content = \System\File::read($f);
			} else {
				ob_start();

				try {
					$cache = cfg('cache', 'resources');
				} catch (\System\Error $e) {
					$cache = false;
				}

				foreach ($files[self::KEY_FOUND] as $file) {
					include $file;
				}

				foreach ($files[self::KEY_MISSING] as $file) {
					echo sprintf($info[self::KEY_STRING_NOT_FOUND], $file);
				}

				$content = \System\Minifier::process($info['type'], ob_get_clean());

				if ($cache) {
					\System\File::put(self::get_cache_path($info, $files[self::KEY_SUM]), $content);
				}
			}

			return $content;
		}


		/** Get list of available files from user input
		 * @param string $type
		 * @param list   $modules
		 * @return array
		 */
		public static function file_list($type, array $modules = array())
		{
			$info  = self::get_type_info($type);
			$found = array();
			$missing = array();

			if (is_dir(ROOT.$info[self::KEY_DIR_FILES])) {
				foreach ($modules as $module) {
					if ($module !== self::SYMBOL_NOESS) {
						$mod_found = false;

						foreach ($info[self::KEY_POSTFIXES] as $postfix) {
							if (file_exists($p = ROOT.$info[self::KEY_DIR_FILES]."/".$module.'.list')) {
								$list = self::file_list($type, array_map('trim', array_filter(explode("\n", \System\File::read($p)))));
								$found = array_merge($found, $list[self::KEY_FOUND]);
								$missing = array_merge($missing, $list[self::KEY_MISSING]);
								$mod_found = true;
							} elseif (file_exists($p = ROOT.$info[self::KEY_DIR_FILES]."/".$module.'.'.$postfix)) {
								$found[] = $p;
								$mod_found = true;
								break;
							}
						}

						if (!$mod_found) {
							$missing[] = $module;
						}
					}
				}
			}

			return array(
				self::KEY_FOUND   => $found,
				self::KEY_MISSING => $missing,
				self::KEY_SUM     => self::get_module_sum_from_list($modules),
			);
		}


		/** Get name of file to cache content in
		 * @param array $info
		 * @param string $sum
		 * @return string
		 */
		private static function get_cache_name(array $info, $sum)
		{
			return $info['type'].'/'.$sum.(any($info[self::KEY_POSTFIXES]) ? '.'.($info[self::KEY_POSTFIXES][0]):'');
		}


		/** Get path of caching file
		 * @param array $info
		 * @param string $sum
		 * @return string
		 */
		private static function get_cache_path(array $info, $sum)
		{
			return ROOT.self::DIR_TMP.'/'.self::get_cache_name($info, $sum);
		}


		/** Get md5 sum of modules
		 * @param list $modules
		 * @return string
		 */
		private static function get_module_sum_from_list(array $modules)
		{
			return self::get_module_sum(implode(':', $modules));
		}


		/** Get md5 sum of module
		 * @param string $str
		 * @return string
		 */
		private static function get_module_sum($str)
		{
			return md5($str);
		}


		/** Get list of modules from name passed from URL
		 * @param string $type
		 * @param string $name
		 * @return string
		 */
		public static function get_module_list($type, $name)
		{
			if (strpos($name, ':') === 0) {
				$content = array_filter(explode(':', $name));
				self::resource_list_save($type, $content);
				$name = self::get_resource_list_name($content);

				redirect_now('/share/resource/'.$type.'/'.self::get_resource_list_wget_name($type, $name), \System\Http\Response::MOVED_PERMANENTLY);
			} else {
				if ($list = \System\File::read($p = self::get_resource_list_path($type, self::strip_serial($name)), true)) {
					return explode("\n", $list);
				} else return array();
			}
		}


		public static function get_type_info($type)
		{
			if (array_key_exists($type, self::$types)) {
				$info = self::$types[$type];
				$info[self::KEY_TYPE] = $type;
				return $info;
			} else throw new \System\Error\Argument('Resource of type "'.$type.'" does not exist.');
		}


		public static function send_header($type, $length)
		{
			$info = self::get_type_info($type);

			header("HTTP/1.1 200 OK");
			header('Content-Type: '.$info['content']);
			header('Content-Length: '.$length);

			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if (!$debug) {
				header("Pragma: public,max-age=".self::MAX_AGE);
				header('Cache-Control: public');
				header('Expires: '.date(\DateTime::RFC1123, time() + self::MAX_AGE + rand(0,60)));
				header('Age: 0');
			}
		}


		public static function filter_output_content($type, &$content)
		{
			if (is_array($content)) {
				if (any($content)) {
					self::resource_list_save($type, $content);
					$name = self::get_resource_list_name($content);
					$content = self::get_resource_list_wget_name($type, $name);
				} else $content = null;
			}
		}


		private static function resource_list_save($type, &$content)
		{
			$content = array_unique($content);
			$name = self::get_resource_list_name($content);
			$file = self::get_resource_list_path($type, $name);

			if (!file_exists($file)) {
				\System\File::put($file, implode(NL, $content));
			}
		}


		public static function get_resource_list_wget_name($type, $name, $postfix = null)
		{
			$postfix = is_null($postfix) ? self::get_type_postfix($type):$postfix;
			return $name.'.'.self::get_serial().($postfix ? '.'.$postfix:'');
		}


		private static function get_type_postfix($type)
		{
			if (isset(self::$types[$type][self::KEY_POSTFIXES])) {
				return first(self::$types[$type][self::KEY_POSTFIXES]);
			} else return false;
		}


		public static function get_resource_list_name(array $content)
		{
			return md5(implode(':', $content));
		}


		public static function get_resource_list_path($type, $name)
		{
			return ROOT.self::DIR_TMP.'/'.$type.'/'.$name.'.list';
		}


		public static function get_serial()
		{
			if (is_null(self::$serial)) {
				try {
					$debug = cfg('dev', 'debug') && cfg('dev', 'disable', 'serial');
				} catch(\System\Error $e) {
					$debug = true;
				}

				if ($debug) {
					self::$serial = rand(0, PHP_INT_MAX);
				} else {
					self::$serial = cfg('cache', 'resource', 'serial');
				}
			}

			return self::$serial;
		}


		public static function strip_serial($name)
		{
			return first(explode('.', $name));
		}


		public static function is_resource_url($url)
		{
			return !!preg_match(self::URL_BASE, $url);
		}
	}
}
