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
		const DIR_CLASS = '/lib/class';
		const SEP_CLASS = '\\';
		const SEP_LINK  = '_';
		const SEP_MODEL = '.';


		/** Run load all classes only once */
		private static $loaded = false;

		private static $ready = false;


		public static function init()
		{
			if (!self::$ready) {
				if (file_exists($f = BASE_DIR.'/lib/vendor/autoload.php')) {
					require_once $f;
				}

				spl_autoload_register(array('\System\Loader', 'autoload'), true, true);
				self::$ready = true;
			}
		}



		/** Load all available classes
		 * @return void
		 */
		public static function load_all()
		{
			if (!self::$loaded) {
				$dirs = \System\Composer::list_dirs(self::DIR_CLASS);

				foreach ($dirs as $dir) {
					self::load_dir($dir);
				}

				self::$loaded = true;
			}
		}


		/** Load all classes inside directory
		 * @param string $dir
		 * @return void
		 */
		public static function load_dir($dir)
		{
			$files = \System\Directory::find_all_files($dir);

			foreach ($files as $file) {
				require_once $file;
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
			return ucfirsts($model, self::SEP_MODEL, self::SEP_CLASS);
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
			$found = false;
			$file = \System\Loader::get_class_file_name($class_name, true);
			$helper_pos = strpos(\System\Loader::get_link_from_class($class_name), 'helper');
			$is_helper = $helper_pos !== false && $helper_pos <= 1;

			$classes = \System\Composer::list_dirs('/lib/class');

			foreach ($classes as $dir) {
				if (!$is_helper && file_exists($f = $dir.'/'.$file)) {
					$found = include_once($f);
					break;
				}
			}

			if (!$found && $is_helper) {
				$helpers = \System\Composer::list_dirs('/lib/helper');

				$file = explode('/', $file);
				unset($file[0]);
				$file = implode('/', $file);

				foreach ($helpers as $dir) {
					if (file_exists($f = $dir.'/'.$file)) {
						$found = include_once($f);
						break;
					}
				}
			}
		}
	}
}
