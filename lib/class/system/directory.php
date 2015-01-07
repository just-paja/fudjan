<?

/** Directory handling
 * @package system
 * @subpackage files
 */
namespace System
{
	/** Container class that handles directory functions. Throws pwf native
	 * errors. You are encouraged to use this class for directory operations.
	 * @package system
	 * @subpackage files
	 */
	abstract class Directory
	{
		const MOD_DEFAULT = 0775;


		/** Creates directory or throws exception on fail
		 * @param string $pathname
		 * @param int    $mode     Mode in octal
		 * @return bool
		 */
		public static function create($pathname, $mode = self::MOD_DEFAULT)
		{
			if (strpos($pathname, '/') !== false) {
				$pathname = explode('/', $pathname);
			}

			if (is_array($pathname)) {
				$current_dir = '';
				$create = array();

				do {
					$current_dir = implode('/', $pathname);

					if (is_dir($current_dir)) {
						break;
					}

					$create[] = array_pop($pathname);
				} while (any($pathname));

				if (any($create)) {
					$create = array_reverse($create);
					$current_dir = implode('/', $pathname);

					foreach ($create as $dir) {
						$current_dir .= '/'.$dir;

						if (!is_dir($current_dir)) {
							if (!($action = @mkdir($current_dir, $mode))) {
								throw new \System\Error\Permissions(sprintf('Failed to create directory on path "%s" in mode "%s". Please check your permissions.', $current_dir, base_convert($mode, 10, 8)));
							}
						}
					}
				}
			} else {
				if (!($action = @mkdir($pathname, $mode, true))) {
					throw new \System\Error\Permissions(sprintf('Failed to create directory on path "%s" in mode "%s". Please check your permissions.', $pathname, base_convert($mode, 10, 8)));
				}
			}


			return $action;
		}


		/** Checks if directory exists and attempts to create it
		 * @param string $pathname
		 * @param bool   $create
		 * @param int    $mode     Mode in onctal
		 * @return bool
		 */
		public static function check($pathname, $create = true, $mode = self::MOD_DEFAULT)
		{
			if (!($action = is_dir($pathname)) && $create) {
				$action = self::create($pathname, $mode);
			}

			return $action;
		}


		/** Find all files in path
		 * @param string  $path   Path where the search will occur
		 * @param array  &$files  File list will be put there
		 * @param string  $regexp Files will be filtered using this regular expression
		 * @return list
		 */
		public static function find_all_files($path, &$files = array(), $regexp = null)
		{
			$dir = opendir($path);

			while ($file = readdir($dir)) {
				if (strpos($file, '.') !== 0) {
					if (is_dir($p = $path.'/'.$file)) {
						self::find_all_files($p, $files, $regexp);
					} elseif ($regexp === null || preg_match($regexp, $file)) {
						$files[] = $p;
					}
				}
			}

			return $files;
		}


		/** Find all children
		 * @param string  $path   Path where the search will occur
		 * @return list
		 */
		public static function ls($path, $mod='')
		{
			$files = array();

			if (is_dir($path)) {
				$dir = opendir($path);

				while ($file = readdir($dir)) {
					if (strpos($file, '.') !== 0) {
						if ($mod) {
							if (strpos($mod, 'd') !== false && is_dir($path.'/'.$file)) {
								$files[] = $file;
							}
						} else {
							$files[] = $file;
						}
					}
				}

				closedir($dir);
			}

			return $files;
		}


		/** Simplified find function that just returns list of files
		 * @param string  $path   Path where the search will occur
		 * @param array  &$files  File list will be put there
		 * @param string  $regexp Files will be filtered using this regular expression
		 * @return list
		 */
		public static function find($path, $regexp = null)
		{
			$files = array();
			self::find_all_files($path, $files, $regexp);
			return $files;
		}


		/** Remove directory and files within
		 * @param string $path
		 * @return void
		 */
		public static function remove($path)
		{
			if (strpos('..', $path) === false) {
				if (is_dir($path)) {
					$dp = opendir($path);

					while ($f = readdir($dp)) {
						if ($f != '.' && $f != '..') {
							is_dir($path.'/'.$f) ?
								self::remove($path.'/'.$f):
								unlink($path.'/'.$f);
						}
					}

					rmdir($path);
				}
			}
		}


		public static function copy($src, $dest)
		{
			if (!is_dir($src)) {
				throw new \System\Error\Wtf('Source must be a directory', $src);
			}

			$dir = new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS);
			$iter = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);

			self::check($dest);

			foreach ($iter as $item) {
				if ($item->isDir()) {
					self::check($dest.DIRECTORY_SEPARATOR.$iter->getSubPathName());
				} else {
					copy($item, $dest.DIRECTORY_SEPARATOR.$iter->getSubPathName());
				}
			}
		}
	}
}
