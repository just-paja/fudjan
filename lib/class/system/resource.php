<?

namespace System
{
	abstract class Resource
	{
		const TYPE_SCRIPTS = 'scripts';
		const TYPE_STYLES  = 'styles';
		const TYPE_PIXMAPS = 'pixmaps';
		const TYPE_ICONS   = 'icons';
		const TYPE_THUMB   = 'thumb';
		const TYPE_FONT    = 'font';

		const SYMBOL_NOESS = 'noess';
		const DIR_TMP      = '/var/cache';
		const URL_MATCH    = '/\/share\/resource\/([a-zA-Z]+)\/([0-9a-zA-Z\.\-\/;_=:]*)$/';

		const SCRIPTS_DIR = '/share';
		const SCRIPTS_STRING_NOT_FOUND = 'console.log("Jaffascript module not found: %s");';

		const STYLES_DIR = '/share';
		const STYLES_STRING_NOT_FOUND = '/* Style not found: %s */';

		const FONTS_DIR = '/share/fonts';

		const KEY_SUM              = 'sum';
		const KEY_TYPE             = 'type';
		const KEY_FOUND            = 'found';
		const KEY_MISSING          = 'missing';
		const KEY_DIR_FILES        = 'modules';
		const KEY_DIR_CONTENT      = 'content';
		const KEY_STRING_NOT_FOUND = 'not_found_string';
		const KEY_POSTFIXES        = 'postfixes';
		const KEY_CALLBACK_RESOLVE = 'resolve';
		const KEY_CALLBACK_PARSE   = 'parse';

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
				self::KEY_POSTFIXES        => array('css', 'less'),
				self::KEY_CALLBACK_PARSE   => array('\System\Resource', 'parse_less'),
			),
			self::TYPE_PIXMAPS => array(
				self::KEY_CALLBACK_RESOLVE => array('\System\Image', 'request_pixmap'),
			),
			self::TYPE_ICONS => array(
				self::KEY_CALLBACK_RESOLVE => array('\System\Image', 'request_icon'),
			),
			self::TYPE_THUMB => array(
				self::KEY_DIR_FILES        => \System\Cache\Thumb::DIR,
				self::KEY_CALLBACK_RESOLVE => array('\System\Image', 'request_thumb'),
			),
			self::TYPE_FONT => array(
				self::KEY_DIR_FILES        => self::FONTS_DIR,
				self::KEY_CALLBACK_RESOLVE => array('\System\Resource', 'request_font'),
			),
		);


		/** Serve request
		 * @return void
		 */
		public static function request(\System\Http\Request $request, $type, $path)
		{
			$info = self::get_type_info($type);
			$modules = self::get_module_list($info['type'], $path);
			$info['type'] = $type;
			$info['path'] = $path;

			if (any($modules)) {
				$files   = self::file_list($info[self::KEY_TYPE], $modules);
				$content = self::get_content($info, $files);

				self::send_header($info['type'], strlen($content));
				echo $content;
			} else if (isset($info[self::KEY_CALLBACK_RESOLVE])) {
				return call_user_func_array($info[self::KEY_CALLBACK_RESOLVE], array($request, $info));
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
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if (!$debug && file_exists($f = self::get_cache_path($info, $files[self::KEY_SUM]))) {
				$content = \System\File::read($f);
			} else {
				$content = '';

				try {
					$cache = \System\Settings::get('cache', 'resources');
				} catch (\System\Error $e) {
					$cache = false;
				}

				if (any($info[self::KEY_CALLBACK_PARSE])) {
					$content = call_user_func_array($info[self::KEY_CALLBACK_PARSE], array($info, $files));
				} else {
					$content = self::get_content_from_files($info, $files);
				}

				if (!$debug) {
					$content = \System\Minifier::process($info['type'], $content);
				}

				if ($cache && !$debug) {
					\System\File::put(self::get_cache_path($info, $files[self::KEY_SUM]), $content);
				}
			}

			return $content;
		}


		public static function request_font(\System\Http\Request $rq, $info)
		{
			$dir = '/share/fonts/'.dirname($info['matches'][2]);
			$name = explode('.', basename($info['matches'][2]));
			$suffix = array_pop($name);
			$serial = array_pop($name);
			$name[] = $suffix;
			$name = implode('.', $name);
			$regex = '/^'.str_replace(array('.', '/'), array('\.', '\/'), $name).'$/';
			$files = \System\Composer::find($dir, $regex);

			if (any($files)) {
				$file = \System\File::from_path($files[0]);
				$file->read_meta()->load();

				self::send_header(self::TYPE_FONT, $file->size());
				header('Content-Type: '.$file->mime);
				echo $file->get_content();
				return;
			}

			throw new \System\Error\NotFound();
		}


		public static function parse_less(array $info, array $files)
		{
			$content = '';

			if (class_exists('\Less_Parser')) {
				$parser = new \Less_Parser();

				foreach ($files[self::KEY_MISSING] as $file) {
					$content .= sprintf($info[self::KEY_STRING_NOT_FOUND], $file);
				}

				foreach ($files[self::KEY_FOUND] as $file) {
					$data = self::tags(file_get_contents($file));

					try {
						$parser->parse($data);
					} catch(\Exception $e) {
						throw new \System\Error\Format('Error while parsing LESS styles', $e->getMessage(), $file);
					}
				}

				$content .= $parser->getCss();
				return $content;
			} else throw new \System\Error\MissingDependency('Missing less parser', 'install oyejorge/less.php');
		}


		public static function get_content_from_files(array $info, array $files)
		{
			$content = '';

			foreach ($files[self::KEY_MISSING] as $file) {
				$content .= sprintf($info[self::KEY_STRING_NOT_FOUND], $file);
			}

			foreach ($files[self::KEY_FOUND] as $file) {
				$content .= self::tags(file_get_contents($file));
			}

			return $content;
		}


		public static function tags($content)
		{
			return preg_replace_callback('/<([A-Za-z_]+)\(([0-9A-Za-z\/\.\,\-]+)\)(\w\?|\w?([^>]+)+)?>/', array('\System\Resource', 'resolve_tag'), $content);
		}


		public static function resolve_tag($matches)
		{
			$tag = $matches[1];
			$args = explode(',', $matches[2]);
			$result = self::tag($tag, $args);

			if (any($matches[3])) {
				$pipes = preg_replace('/^[^a-zA-Z]+/', '', trim($matches[3]));
				$pipes = explode('|', $pipes);

				foreach ($pipes as $pipe) {
					if (!is_array($result)) {
						$result = array($result);
					}

					$result = self::tag(trim($pipe), $result);
				}
			}

			return $result;
		}


		public static function tag($name, array $args)
		{
			$tags = \System\Settings::get('resources', 'tags');
			$result = null;
			$matched = false;

			foreach ($tags as $cfg_tag) {
				if ($cfg_tag['name'] == $name) {
					$result = call_user_func_array($cfg_tag['callback'], $args);
					$matched = true;
				}
			}

			if ($matched) {
				return $result;
			}

			throw new \System\Error\Config("Tag not found", $name);
		}


		public static function tag_cfg()
		{
			return \System\Settings::get(func_get_args());
		}


		public static function tag_resource($type, $name, $suffix)
		{
			return self::get_resource_list_wget_name($type, $name).'.'.$suffix;
		}


		public static function tag_icon($name, $size)
		{
			$name = explode('/', $name);
			$cat = array_shift($name);
			array_unshift($name, $size);
			array_unshift($name, $cat);

			return self::tag_resource(self::TYPE_ICONS, implode('/', $name), '.png');
		}


		public static function tag_pixmap($name)
		{
			$name = explode('.', $name);
			$suffix = array_pop($name);
			$name = implode('.', $name);

			return self::tag_resource(self::TYPE_PIXMAPS, $name, $suffix);
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
			$dirs = \System\Composer::list_dirs($info[self::KEY_DIR_FILES]);

			foreach ($modules as $module) {
				$mod_found = false;

				foreach ($dirs as $dir) {
					if ($module !== self::SYMBOL_NOESS) {

						foreach ($info[self::KEY_POSTFIXES] as $postfix) {
							if (file_exists($p = $dir."/".$module.'.list')) {
								$list = self::file_list($type, array_map('trim', array_filter(explode("\n", \System\File::read($p)))));
								$found = array_merge($found, $list[self::KEY_FOUND]);
								$missing = array_merge($missing, $list[self::KEY_MISSING]);
								$mod_found = true;
								break;
							} elseif (file_exists($p = $dir."/".$module.'.'.$postfix)) {
								$found[] = $p;
								$mod_found = true;
								break;
							}
						}
					}

					if ($mod_found) {
						break;
					}
				}

				if (!$mod_found) {
					$missing[] = $module;
				}
			}

			return array(
				self::KEY_FOUND   => array_filter($found),
				self::KEY_MISSING => array_filter($missing),
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
			return BASE_DIR.self::DIR_TMP.'/'.self::get_cache_name($info, $sum);
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
			} else {
				try {
					$debug = \System\Settings::get('dev', 'debug', 'backend');
				} catch(\System\Error $e) {
					$debug = true;
				}

				if ($debug) {
					throw new \System\Error\Argument('Resource of type "'.$type.'" does not exist.');
				} else {
					throw new \System\Error\NotFound();
				}
			}
		}


		public static function send_header($type, $length)
		{
			$info = self::get_type_info($type);

			header("HTTP/1.1 200 OK");

			if (any($info[self::KEY_DIR_CONTENT])) {
				header('Content-Type: '.$info[self::KEY_DIR_CONTENT]);
			}

			header('Content-Length: '.$length);
			header('Access-Control-Allow-Origin: *');

			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
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
				} else {
					$content = null;
				}
			}

			return $content;
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

			try {
				$domain = \System\Settings::get('resources', 'domain');
			} catch (\System\Error\Config $e) {
				$domain = null;
			}

			return ($domain ? '//'.$domain:'').'/res/'.$type.'/'.$name.'.'.self::get_serial().($postfix ? '.'.$postfix:'');
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
			return BASE_DIR.self::DIR_TMP.'/'.$type.'/'.$name.'.list';
		}


		public static function get_serial()
		{
			if (is_null(self::$serial)) {
				try {
					$debug = \System\Settings::get('dev', 'debug', 'backend') || \System\Settings::get('dev', 'disable', 'serial');
				} catch(\System\Error $e) {
					$debug = true;
				}

				if ($debug) {
					self::$serial = rand(0, PHP_INT_MAX);
				} else {
					self::$serial = \System\Settings::get('cache', 'resource', 'serial');
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
			return !!preg_match(self::URL_MATCH, $url);
		}
	}
}
