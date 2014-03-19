<?

/** Database data model handling */
namespace System\Model
{
	abstract class Perm extends Database
	{
		const CREATE = 'created';
		const BROWSE = 'browsed';
		const VIEW   = 'viewed';
		const EDIT   = 'updated';
		const DROP   = 'dropped';

		/** Ask if user has right to do this
		 * @param string      $method One of created, browsed
		 * @param System\User $user   User to get perms for
		 */
		public static function can_be_created($method, \System\User $user)
		{
			return true;
		}


		/** Ask if user has right to do this
		 * @param string      $method One of viewed, updated, dropped
		 * @param System\User $user   User to get perms for
		 */
		public function can_be($method, \System\User $user)
		{
			return true;
		}
	}
}
