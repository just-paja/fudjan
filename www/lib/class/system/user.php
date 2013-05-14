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
		const COOKIE_USER = 'pwf_user';

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

		/** Rights are cached here, within the object */
		private $rights;


		/** Create guest user
		 * @return System\User
		 */
		public static function guest()
		{
			return new self(array(
				"user_id" => 0,
				"nick"    => l('anonymous'),
				"image"   => \System\Image::from_path("/share/pixmaps/pwf/anonymous_user.png"),
			));
		}


		/** Login selected user
		 * @param self   $user
		 * @param string $password
		 * @return bool
		 */
		public function login(\System\Http\Request $request, $password)
		{
			return $this->password == hash_passwd($password) ?
				$this->create_session($request):false;
		}


		/** Create user session
		 * @param self $user
		 * @return bool
		 */
		private function create_session(\System\Http\Request $request)
		{
			$request->user = $this;
			$this->last_login = new \DateTime();
			$this->save();
			$_SESSION['yacms-user-id'] = $this->id;

			return true;
		}


		/** Logout current active user
		 * @return bool
		 */
		public function logout()
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
			if ($to === '*') {
				$has = cfg('site', 'modules', 'allow_by_default');
			} else {
				if (empty($this->rights)) {
					$this->get_rights();
				}

				$has = array_key_exists($to, $this->rights);
			}

			return $this->is_root() || $has;
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
