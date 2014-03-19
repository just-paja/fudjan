<?

/** Database data model handling */
namespace System\Model
{
	abstract class Perm extends Database
	{
		const CREATE = 'create';
		const BROWSE = 'browse';
		const UPDATE = 'update';
		const DROP   = 'drop';
		const VIEW   = 'view';


		/** Get default config for this action
		 * @param string $method One of permission method constants
		 * @return bool
		 */
		public static function get_default_for($method)
		{
			try {
				$res = cfg('api', 'allow', $method);
			} catch (\System\Error\Config $e) {
				throw new \System\Error\Argument('Unknown permission method type.', $method);
			}

			return !!$res;
		}


		/** Ask if user has right to do this
		 * @param string      $method One of created, browsed
		 * @param System\User $user   User to get perms for
		 * @return bool
		 */
		public static function can_be_created($method, \System\User $user)
		{
			return self::get_default_for($method);
		}


		/** Ask if user has right to do this
		 * @param string      $method One of viewed, updated, dropped
		 * @param System\User $user   User to get perms for
		 * @return bool
		 */
		public function can_be($method, \System\User $user)
		{
			return self::get_default_for($method);
		}
	}
}
