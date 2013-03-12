<?

/** System users
 * @package system
 * @subpackage users
 */
namespace System
{
	/** System user database model with user groups, permissions and contacts
	 * @uses \System\User\Group
	 * @uses \System\User\Contact
	 * @package system
	 * @subpackage users
	 */
	class User extends Model\Database
	{
		static protected $required = array('login');
		static protected $attrs = array(
			"login"       => array('varchar', "is_unique" => true),
			"nick"        => array('varchar'),
			"first_name"  => array('varchar'),
			"last_name"   => array('varchar'),
			"password"    => array('password', "default" => ''),
			"avatar"      => array('image'),
			"last_login"  => array('datetime', "default" => 0),
			"com_email"   => array('bool', "default" => true),
			"com_sms"     => array('bool', "default" => false),
		);

		static protected $has_many = array(
			"groups" => array("model" => '\System\User\Group', "is_bilinear" => true, "is_master" => true),
			"contacts" => array("model" => '\System\User\Contact')
		);

		/** Current user is placed here */
		static private $current_user;

		/** Rights are cached here, within the object */
		private $rights;

		/** Get current active user
		 * @return System\User
		 */
		public static function get_active()
		{
			if (self::$current_user instanceof self) {
				return self::$current_user;
			} elseif (any($_SESSION['yacms-user-id'])) {
				self::$current_user = find("\System\User", $_SESSION['yacms-user-id']);
			}

			if (!(self::$current_user instanceof self)) {
				self::$current_user = self::create_guest();
			}

			self::$current_user->get_rights();
			return self::$current_user;
		}


		/** Create guest user
		 * @return System\User
		 */
		private static function create_guest()
		{
			return new self(array("user_id" => 0, "nick" => _('Host'), "anonym_key" => session_id()));
		}


		/** Is anyone logged in?
		 * @return bool
		 */
		public static function logged_in()
		{
			if(!self::$current_user){
				self::get_active();
			}
			return @!!self::$current_user->id;
		}


		/** Login selected user
		 * @param self   $user
		 * @param string $password
		 * @return bool
		 */
		public static function login(self $user, $password)
		{
			return $user->password == hash_passwd($password) ?
				self::create_session($user):
				false;
		}


		/** Create user session
		 * @param self $user
		 * @return bool
		 */
		private static function create_session(self $user)
		{
			self::$current_user = $user;
			$user->last_login = new \DateTime();
			$user->save();
			$_SESSION['yacms-user-id'] = $user->id;
			return true;
		}


		/** Logout current active user
		 * @return bool
		 */
		public static function logout()
		{
			unset($_SESSION['yacms-user-id']);
			return true;
		}


		/** Get full user name
		 * @param string $pattern Format of the name
		 * @return string
		 */
		public function get_name($pattern = null)
		{
			return $pattern ? soprintf($pattern, $this):$this->first_name.' '.$this->last_name;
		}


		/** Get all users permissions
		 * @return array Set of permissions (System\User\Perm)
		 */
		public function get_rights()
		{
			if (is_null($this->rights)) {
				$conds = array("public" => true);
				$ids = collect_ids($this->groups->fetch());

				if (any($ids)) {
					$conds[] = "id_system_user_group IN (".implode(',', $ids).")";
				}

				$this->rights = get_all("\System\User\Perm")
					->where($conds, "t0", true)
					->reset_cols()
					->add_cols(array("trigger", "type", "id_system_user_perm"), "t0")
					->assoc_with('')
					->fetch('trigger', 'id_system_user_perm');

			}

			return $this->rights;
		}


		/** Has user right to do action
		 * @param string $to
		 * @return bool
		 */
		public function has_right($to)
		{
			if (empty($this->rights)) {
				$this->get_rights();
			}

			return $this->login == 'root' || array_key_exists($to, $this->rights);
		}


		/** Static version of System\User::has_right for current user
		 * @param string $what
		 * @return bool
		 */
		public static function has_right_to($what)
		{
			return self::get_active()->has_right($what);
		}


		/** Is active user root?
		 * @return bool
		 */
		public function is_root()
		{
			return $this->login == 'root';
		}


		/** Get all available ways to send notifications
		 * @return string[] Set of ways
		 */
		public function get_mailer_types()
		{
			return array('email');
		}


		/** Wrapper for delete - forbids deleting root user
		 * @return bool
		 */
		public function delete()
		{
			return $this->is_root() ? $this:parent::delete();
		}


		/** Wrapper for parent::seoname()
		 * @return string
		 */
		public function get_seoname()
		{
			$this->name = $this->get_name();
			return parent::get_seoname();
		}


		/** Get IDs of all system groups
		 * @return int[] Set of group IDs
		 */
		function get_group_ids()
		{
			return collect_ids($this->groups->fetch());
		}


		/** Get all user settings
		 * @return array
		 */
		function get_setup()
		{
			if (!$this->setup) {
				$this->setup = User\Setup::get_for_user($this->id);
			}

			return $this->setup;
		}


		/** Get all user flags - some system behavior changes
		 * @return string Space separated flags
		 */
		public static function get_flags()
		{
			$flags = array();
			$flags[] = self::logged_in() ? 'user':'guest';
			return implode(' ', $flags);
		}


		/** Generate random password
		 * @param int $len
		 * @return string
		 */
		public static function gen_passwd($len = 12)
		{
			return implode('-', str_split(substr(md5(rand(1,4096*4096)), 0, $len), 4));
		}
	}
}
