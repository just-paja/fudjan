<?

namespace System
{
	abstract class Composer
	{
		const DIR_VENDOR = '/lib/vendor';

		protected static $libs = null;
		/*
		 * etc/default/conf.d
		 * etc/default/routes.d
		 * etc/locales
		 * lib/module
		 * lib/template
		 */

		public static function get_libs()
		{
			if (self::$libs === null) {
				$libs    = array();
				$base    = BASE_DIR.self::DIR_VENDOR;
				$vendors = \System\Directory::ls($base, 'd');

				foreach ($vendors as $vendor) {
					$vendor_path = $base.'/'.$vendor;
					$local = \System\Directory::ls($vendor_path, 'd');

					foreach ($local as $key=>$lib) {
						$local[$key] = $vendor.'/'.$lib;
					}

					$libs = array_merge($libs, $local);
				}

				self::$libs = $libs;
			}

			return self::$libs;
		}


		/**
		 * @returns array List of directories
		 */
		public static function list_dirs($relative_path)
		{
			$list = array();
			$libs = self::get_libs();

			if (is_dir(ROOT.$relative_path)) {
				$list[] = realpath(ROOT.$relative_path);
			}

			foreach ($libs as $lib) {
				$dir = BASE_DIR.self::DIR_VENDOR.'/'.$lib.'/'.$relative_path;

				if (\System\Directory::check($dir, false)) {
					$list[] = realpath($dir);
				}
			}

			if (BASE_DIR != ROOT && is_dir(BASE_DIR.$relative_path)) {
				$list[] = realpath(BASE_DIR.$relative_path);
			}

			return array_unique($list);
		}


		public static function list_files($relative_path)
		{
			$dirs = self::list_dirs($relative_path);
			$files = array();

			foreach ($dirs as $dir) {
				\System\Directory::find_all_files($dir, $files);
			}

			return $files;
		}


		public static function find($path, $regexp = null)
		{
			$dirs = self::list_dirs($path);
			$files = array();

			foreach ($dirs as $dir) {
				\System\Directory::find_all_files($dir, $files, $regexp);
			}

			return $files;
		}


		public static function ls($dir, $file)
		{
			$dirs  = self::list_dirs($dir);
			$files = array();

			foreach ($dirs as $dir) {
				if (file_exists($p = $dir.'/'.$file)) {
					$files[] = $p;
				}
			}

			return $files;
		}


		public static function resolve($path)
		{
			$dir = dirname($path);
			$file = basename($path);
			$files = self::ls($dir, $file);

			if (count($files) <= 1) {
				return any($files) ? $files[0]:null;
			}

			throw new \System\Error\File('Cannot resolve path. Found duplicate files.', $path, var_export($files, true));
		}
	}
}
