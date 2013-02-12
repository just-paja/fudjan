<?

namespace System
{
	class Loader
	{
		const DIR_CLASS = '/lib/class';
		private static $loaded = false;


		/** Load all available classes
		 * @returns void
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
		 * @param string $class_name
		 * @todo Rewrite not using regexps
		 * @returns string
		 */
		public static function get_class_file_name($class_name, $with_suffix = false)
		{
			return str_replace("\_", '/', substr(strtolower(preg_replace("/([A-Z])/", "_$1", $class_name)), 1)).($with_suffix ? ".php":'');
		}
	}
}
