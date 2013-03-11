<?

namespace System
{
	abstract class Resource
	{
		const TYPE_SCRIPTS = 'scripts';
		const TYPE_STYLES  = 'styles';
		const SYMBOL_NOESS = 'noess';
		const DIR_TMP      = '/var/cache';

		const SCRIPTS_DIR_ESSENTIAL = '/share/scripts/essential';
		const SCRIPTS_DIR_MODULES = '/share/scripts/modules';
		const SCRIPTS_STRING_NOT_FOUND = 'v("Jaffascript module not found: %s");';

		const STYLES_DIR_ESSENTIAL = '/share/styles/essential';
		const STYLES_DIR_MODULES = '/share/styles/modules';
		const STYLES_STRING_NOT_FOUND = '/* Style module not found: %s */';

		const KEY_SUM              = 'sum';
		const KEY_TYPE             = 'type';
		const KEY_FOUND            = 'found';
		const KEY_MISSING          = 'missing';
		const KEY_DIR_ESSENTIAL    = 'essential';
		const KEY_DIR_MODULES      = 'modules';
		const KEY_DIR_CONTENT      = 'content';
		const KEY_STRING_NOT_FOUND = 'not_found_string';
		const KEY_POSTFIXES        = 'postfixes';

		const MAX_AGE = 86400;


		private static $types = array(
			self::TYPE_SCRIPTS => array(
				self::KEY_DIR_ESSENTIAL    => self::SCRIPTS_DIR_ESSENTIAL,
				self::KEY_DIR_MODULES      => self::SCRIPTS_DIR_MODULES,
				self::KEY_STRING_NOT_FOUND => self::SCRIPTS_STRING_NOT_FOUND,
				self::KEY_DIR_CONTENT      => 'text/javascript',
				self::KEY_POSTFIXES        => array('js'),

			),
			self::TYPE_STYLES => array(
				self::KEY_DIR_ESSENTIAL    => self::STYLES_DIR_ESSENTIAL,
				self::KEY_DIR_MODULES      => self::STYLES_DIR_MODULES,
				self::KEY_STRING_NOT_FOUND => self::STYLES_STRING_NOT_FOUND,
				self::KEY_DIR_CONTENT      => 'text/css',
				self::KEY_POSTFIXES        => array('css'),
			),
		);


		public static function request()
		{
			$info    = self::get_type_info(\System\Input::get('type'));
			$modules = self::get_module_list($info['type'], \System\Input::get('modules'));
			$files   = self::file_list($info[self::KEY_TYPE], $modules);
			$content = self::get_content($info, $files);

			self::send_header($info['type'], strlen($content));
			echo $content;
		}


		private static function get_content(array $info, array $files)
		{
			if (!cfg('dev', 'debug') && file_exists($f = self::get_cache_path($info, $files[self::KEY_SUM]))) {
				$content = \System\File::read($f);
			} else {
				ob_start();

				foreach ($files[self::KEY_FOUND] as $file) {
					include $file;
				}

				foreach ($files[self::KEY_MISSING] as $file) {
					echo sprintf($info[self::KEY_STRING_NOT_FOUND], $file);
				}

				$content = \System\Minifier::process($info['type'], ob_get_clean());
				\System\File::put(self::get_cache_path($info, $files[self::KEY_SUM]), $content);
			}

			return $content;
		}


		public static function file_list($type, array $modules = array())
		{
			$info  = self::get_type_info($type);
			$found = array();
			$missing = array();

			if (!in_array(self::SYMBOL_NOESS, $modules) && is_dir(ROOT.$info[self::KEY_DIR_ESSENTIAL])) {
				$dir = opendir(ROOT.$info[self::KEY_DIR_ESSENTIAL]);
				while ($f = readdir($dir)) {
					if (strpos($f, ".") !== 0) {
						$found[] = $f;
					}
				}

				sort($found);

				foreach ($found as &$f) {
					$f = ROOT.$info[self::KEY_DIR_ESSENTIAL].'/'.$f;
				}
			}

			if (is_dir(ROOT.$info[self::KEY_DIR_MODULES])) {
				foreach ($modules as $module) {
					if ($module !== self::SYMBOL_NOESS) {
						$mod_found = false;

						foreach ($info[self::KEY_POSTFIXES] as $postfix) {
							if (file_exists($p = ROOT.$info[self::KEY_DIR_MODULES]."/".$module.'.'.$postfix)) {
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


		private static function get_cache_name(array $info, $sum)
		{
			return $info['type'].'/'.$sum.(any($info[self::KEY_POSTFIXES]) ? '.'.($info[self::KEY_POSTFIXES][0]):'');
		}


		private static function get_cache_path(array $info, $sum)
		{
			return ROOT.self::DIR_TMP.'/'.self::get_cache_name($info, $sum);
		}


		private static function get_module_sum_from_list(array $modules)
		{
			return self::get_module_sum(implode(':', $modules));
		}


		private static function get_module_sum($str)
		{
			return md5($str);
		}


		public static function get_module_list($type, $name)
		{
			if ($list = \System\File::read(self::get_resource_list_path($type, $name))) {
				return explode("\n", $list);
			} else return array();
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

			if (!cfg('dev', 'debug')) {
				header("Pragma: public,max-age=".self::MAX_AGE);
				header('Cache-Control: public');
				header('Expires: '.date(\DateTime::RFC1123, time() + self::MAX_AGE + rand(0,60)));
				header('Age: 0');
			}
		}


		public static function filter_output_content($type, &$content)
		{
			$content = array_unique($content);
			$name = self::get_resource_list_name($content);
			$file = self::get_resource_list_path($type, $name);

			if (!file_exists($file)) {
				\System\File::put($file, implode(NL, $content));
			}

			$content = $name;
		}


		public static function get_resource_list_name(array $content)
		{
			return md5(implode(':', $content));
		}


		public static function get_resource_list_path($type, $name)
		{
			return ROOT.self::DIR_TMP.'/'.$type.'/'.$name.'.list';
		}
	}
}
