<?

namespace System
{
	abstract class Composer
	{
		const DIR_VENDOR = '/lib/vendor';
		/*
		 * etc/default/conf.d
		 * etc/default/routes.d
		 * etc/locales
		 * lib/module
		 * lib/template
		 */

		/**
		 * @returns array List of directories
		 */
		public static function list_dirs($relative_path)
		{
			$list = array();

			if (is_dir(ROOT.$relative_path)) {
				$list[] = ROOT.$relative_path;
			}

			$vendors = \System\Directory::ls(ROOT.self::DIR_VENDOR, 'd');

			foreach ($vendors as $vendor) {
				if ($vendor == 'composer') {
					continue;
				}

				$vendor_path = ROOT.self::DIR_VENDOR.'/'.$vendor;
				$libs = \System\Directory::ls($vendor_path, 'd');

				foreach ($libs as $lib) {
					$dir = $vendor_path.'/'.$lib.$relative_path;

					if (\System\Directory::check($dir, false)) {
						$list[] = $dir;
					}
				}
			}

			if (BASE_DIR != ROOT && is_dir(BASE_DIR.$relative_path)) {
				$list[] = BASE_DIR.$relative_path;
			}

			return $list;
		}
	}
}
