<?

namespace System
{
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
			if (strpos($pathname, '/')) {
				$pathname = explode('/', $pathname);
			}

			if (is_array($pathname)) {
				$current_dir = '';

				foreach ($pathname as $dir) {
					$current_dir .= '/'.$dir;

					if (!is_dir($current_dir)) {
						$action = self::create($current_dir, $mode);
					}
				}
			} else {
				if (!($action = @mkdir($pathname, $mode, true))) {
					throw new \System\Error\Permissions(sprintf('Failed to create directory on path "%s" in mode "%s". Please check your permissions.', $pathname, $mode));
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
			if (!($action = is_dir($pathname))) {
				$action = self::create($pathname, $mode);
			}

			return $action;
		}


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
	}
}
