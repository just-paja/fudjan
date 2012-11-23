<?

namespace System
{
	class Loader
	{
		const DIR_CLASS = '/lib/class';
		private static $loaded = false;

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
	}
}
