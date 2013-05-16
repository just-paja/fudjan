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

		/** Run load all classes only once */
		private static $loaded = false;


		/** Load all available classes
		 * @return void
		 */
		public static function load_all()
		{
			if (!self::$loaded) {
				$files = \System\Directory::find_all_files(ROOT.self::DIR_CLASS);
				foreach ($files as $file) {
					require_once $file;
				}
				$loaded = true;
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
			return str_replace('::', '_', strtolower($model));
		}


		/** Get class name in link format from standart format
		 * @param string $model
		 * @return string
		 */
		public static function get_link_from_class($model)
		{
			return str_replace('\\', '_', strtolower(preg_replace('/^\\\\/', '', $model)));
		}


		/** Get class name from model format
		 * @param string $model
		 * @return string
		 */
		public static function get_class_from_model($model)
		{
			return ucfirsts($model, '::', '\\');
		}


		/** Get class translation from class format
		 * @param string $class_name Class name in class format
		 * @param bool   $plural     Return plural
		 * @return string
		 */
		public static function get_class_trans($class_name, $plural = false)
		{
			return l('model_'.self::get_link_from_class($class_name).($plural ? '_plural':''));
		}


		/** Get class name in model format from class format
		 * @param string $class_name
		 * @return string
		 */
		public static function get_model_from_class($class_name)
		{
			return ucfirsts($class_name, '\\', '::');
		}
	}
}
