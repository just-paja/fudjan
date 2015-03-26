<?

/** System class loader
 * @package system
 */
namespace System
{
	/** System class loader
	 * @package system
	 * @property $loaded
	 */
	class Loader
	{
		const DIR_LIB    = '/lib';
		const DIR_CLASS  = '/lib/class';
		const DIR_MODULE = '/lib/module';
		const DIR_HELPER = '/lib/helper';
		const DIR_REWRITE = '/etc/rewrite.d';

		const SEP_CLASS = '\\';
		const SEP_LINK  = '_';
		const SEP_MODEL = '.';

		const FILE_REWRITE = '/.htaccess';
		const FILE_CORE    = '/var/cache/runtime/core.php';
		const FILE_MODULES = '/var/cache/runtime/modules.php';


		/** Run load all classes only once */
		private static $loaded = false;

		private static $ready = false;


		public static function init()
		{
			if (!self::$ready) {
				if (file_exists($f = BASE_DIR.'/lib/vendor/autoload.php')) {
					require_once $f;
				}

				spl_autoload_register(array('\System\Loader', 'autoload'), true);
				self::$ready = true;
			}
		}



		/** Load all available classes
		 * @return void
		 */
		public static function load_all()
		{
			if (!self::$loaded) {
				self::load_all_from_dirs(\System\Composer::list_dirs(self::DIR_CLASS));
				self::$loaded = true;
			}
		}



		/**
		 * Load all available modules
		 *
		 * @return void
		 */
		public static function load_all_modules()
		{
			self::load_all_from_dirs(\System\Composer::list_dirs(self::DIR_MODULE));
		}


		/** Load all available classes
		 * @return void
		 */
		public static function load_all_helpers()
		{
			self::load_all_from_dirs(\System\Composer::list_dirs(self::DIR_HELPER));
		}


		public static function load_all_from_dirs($dirs)
		{
			foreach ($dirs as $dir) {
				self::load_dir($dir);
			}
		}

		public static function dump_core($modules = false)
		{
			self::load_all();

			$str   = '<? ';
			$lists = array(
				get_declared_interfaces(),
				get_declared_classes(),
			);

			foreach ($lists as $list) {
				foreach ($list as $name) {
					$index = strpos($name, 'Module');

					if (($modules &&  $index === 0) || (!$modules && $index !== 0)) {
						$base = self::DIR_CLASS;

						if ($index === 0) {
							$base = self::DIR_LIB;
						}

						$file_name = $base.'/'.self::get_class_file_name($name, true);
						$file_path = \System\Composer::resolve($file_name);


						if ($file_path) {
							$cont = php_strip_whitespace($file_path);
							$str .= preg_replace('/^<\?(php)?(.*)(\?>)?$/s', '$2', $cont);
						}
					}
				}
			}

			return $str;
		}


		/** Load all classes inside directory
		 * @param string $dir
		 * @return void
		 */
		public static function load_dir($dir)
		{
			$files = \System\Directory::find_all_files($dir);

			foreach ($files as $file) {
				$fname = str_replace($dir.'/', '', $file);
				$cname = self::get_class_from_file($fname);

				if (!class_exists($cname, false) && !interface_exists($cname, false)) {
					require_once $file;
				}
			}
		}


		/** Get filesystem representation of class name
		 * @param string $class_name  Name of class
		 * @param bool   $with_suffix Return file name with suffix
		 * @todo Rewrite not using regexps
		 * @return string
		 */
		public static function get_class_file_name($class_name, $with_suffix = false)
		{
			return str_replace("\_", '/', substr(strtolower(preg_replace("/([A-Z])/", "_$1", $class_name)), 1)).($with_suffix ? ".php":'');
		}


		public static function get_class_from_file($name)
		{
			$name = str_replace('.php', '', implode('\\', array_map('ucfirst', explode('/', $name))));
			$name = implode('', array_map('ucfirst', explode('_', $name)));
			return $name;
		}


		/** Get class name in link format from model format
		 * @param string $model
		 * @return string
		 */
		public static function get_link_from_model($model)
		{
			return str_replace(self::SEP_MODEL, self::SEP_LINK, strtolower($model));
		}


		/** Get class name in link format from standart format
		 * @param string $model
		 * @return string
		 */
		public static function get_link_from_class($model)
		{
			return str_replace(self::SEP_CLASS, self::SEP_LINK, strtolower(preg_replace('/^\\\\/', '', $model)));
		}


		/** Get class name from model format
		 * @param string $model
		 * @return string
		 */
		public static function get_class_from_model($model)
		{
			return ucfirsts(ucfirsts($model, self::SEP_MODEL, self::SEP_CLASS), '_', '');
		}


		/** Get class name in model format from class format
		 * @param string $class_name
		 * @return string
		 */
		public static function get_model_from_class($class_name)
		{
			return ucfirsts($class_name, self::SEP_CLASS, self::SEP_MODEL);
		}


		public static function autoload($class_name)
		{
			$sources = array('/lib/class', '/lib');

			$found = false;
			$file = \System\Loader::get_class_file_name($class_name, true);
			$helper_pos = strpos(\System\Loader::get_link_from_class($class_name), 'helper');
			$is_helper = $helper_pos !== false && $helper_pos <= 1;

			foreach ($sources as $key=>$source) {
				$classes = \System\Composer::list_dirs($source);

				foreach ($classes as $dir) {
					if (file_exists($f = $dir.'/'.$file)) {
						$found = include_once($f);
						break;
					}
				}

				if ($found) {
					break;
				}
			}

			if (!$found) {
				throw new \System\Error\Code('Class not found', $class_name, $file);
			}
		}
	}
}
