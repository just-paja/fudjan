<?

/** Rewrite handling
 * @package system
 * @subpackage routes
 */
namespace System
{
	/** Rewrite handling
	 * @package system
	 * @subpackage routes
	 */
	abstract class Router
	{
		const DIR_REWRITE = '/etc/rewrite.d';
		const REWRITE_TARGET = '/.htaccess';

		/** Generate rules for mod rewrite htaccess
		 * @return string
		 */
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


		/** Update htaccess rules
		 * @return bool
		 */
		public static function update_rewrite()
		{
			return \System\File::put(ROOT.self::REWRITE_TARGET, self::generate_rewrite_rules());
		}
	}
}
