<?php

namespace Helper
{
	abstract class Htaccess
	{
		/**
		 * Generate rules for mod rewrite htaccess
		 * @return string
		 */
		public static function generate_rewrite_rules()
		{
			$dirs = \System\Composer::list_dirs(\System\Loader::DIR_REWRITE);

			foreach ($dirs as $dir) {
				$od = opendir($dir);
				$files = array();

				while ($file = readdir($od)) {
					if (strpos($file, '.') !== 0) {
						$files[$file] = \System\File::read($dir.'/'.$file);
					}
				}

				ksort($files);
			}

			return implode("\n", $files);
		}
	}
}
