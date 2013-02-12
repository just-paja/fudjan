<?

namespace System
{
	abstract class Router
	{
		const DIR_REWRITE = '/etc/rewrite.d';
		const REWRITE_TARGET = '/.htaccess';


		public static function generate_rewrite_rules()
		{
			$dir = ROOT.self::DIR_REWRITE;
			$od = opendir($dir);
			$files = array();

			while ($file = readdir($od)) {
				if (strpos($file, '.') !== 0) {
					$files[$file] = \System\File::read($dir.'/'.$file);
				}
			}

			ksort($files);
			return implode("\n", $files);
		}


		public static function update_rewrite()
		{
			\System\File::put(ROOT.self::REWRITE_TARGET, self::generate_rewrite_rules());
		}
	}
}
