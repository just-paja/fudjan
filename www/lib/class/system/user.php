<?

namespace System
{
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

		static private $current_user;
		private $rights;

		/** Get current active user
		 * @returns System\User
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
		 * @returns System\User
		 */
		private static function create_guest()
		{
			return new self(array("user_id" => 0, "nick" => _('Host'), "anonym_key" => session_id()));
		}


		/** Is anyone logged in?
		 * @returns bool
		 */
		public static function logged_in()
		{
			if(!self::$current_user){
				self::get_active();
			}
			return @!!self::$current_user->id;
		}


		/** Login selected user
		 * @param System\User $user
		 * @param string      $password
		 * @returns bool
		 */
		public static function login(self $user, $password)
		{
			return $user->password == hash_passwd($password) ?
				self::create_session($user):
				false;
		}


		/** Create user session
		 * @param System\User $user
		 * @returns bool
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
		 * @returns bool
		 */
		public static function logout()
		{
			unset($_SESSION['yacms-user-id']);
			return true;
		}


		/** Get full user name
		 * @param string $pattern Format of the name
		 * @returns string
		 */
		public function get_name($pattern = null)
		{
			return $pattern ? soprintf($pattern, $this):$this->first_name.' '.$this->last_name;
		}


		/** Get all users permissions
		 * @returns array Set of permissions (System\User\Perm)
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
		 * @returns bool
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
		 * @returns bool
		 */
		public static function has_right_to($what)
		{
			return self::get_active()->has_right($what);
		}


		/** Is active user root?
		 * @returns bool
		 */
		public function is_root()
		{
			return $this->login == 'root';
		}


		/** Get all available ways to send notifications
		 * @returns string[] Set of ways
		 */
		public function get_mailer_types()
		{
			return array('email');
		}


		/** Wrapper for delete - forbids deleting root user
		 * @returns bool
		 */
		public function delete()
		{
			return $this->is_root() ? $this:parent::delete();
		}


		/** Wrapper for parent::seoname()
		 * @returns string
		 */
		public function get_seoname()
		{
			$this->name = $this->get_name();
			return parent::get_seoname();
		}


		/** Get IDs of all system groups
		 * @returns int[] Set of group IDs
		 */
		function get_group_ids()
		{
			return collect_ids($this->groups->fetch());
		}


		/** Get all user settings
		 * @returns array
		 */
		function get_setup()
		{
			if (!$this->setup) {
				$this->setup = User\Setup::get_for_user($this->id);
			}

			return $this->setup;
		}


		/** Get all user flags - some system behavior changes
		 * @returns string Space separated flags
		 */
		public static function get_flags()
		{
			$flags = array();
			$flags[] = self::logged_in() ? 'user':'guest';
			return implode(' ', $flags);
		}


		/** Generate random password
		 * @param int $len
		 * @returns string
		 */
		public static function gen_passwd($len = 12)
		{
			return implode('-', str_split(substr(md5(rand(1,4096*4096)), 0, $len), 4));
		}
	}
}
