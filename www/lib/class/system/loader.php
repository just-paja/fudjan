<?

namespace System
{
	class Loader
	{
		const DIR_CLASS = '/lib/class';

		public static function load_all()
		{
			$files = \System\Directory::find_all_files(ROOT.self::DIR_CLASS);
			foreach ($files as $file) {
				require_once $file;
			}
		}
	}
}
