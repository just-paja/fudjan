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
			if (!($action = @mkdir($pathname, $mode, true))) {
				throw new \InternalException(sprintf('Failed to create directory on path "%s" in mode "%s". Please check your permissions.', $pathname, $mode));
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
	}
}
