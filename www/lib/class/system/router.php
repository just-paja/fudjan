<?

namespace System
{
	abstract class Router
	{
		const DIR_REWRITE = '/etc/rewrite.d';


		public static function generate_htaccess()
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
	}
}
