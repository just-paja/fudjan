<?

/** Database data model handling */
namespace System\Model
{
	abstract class Perm extends Filter
	{
		const VIEW_SCHEMA = 'schema';
		const CREATE      = 'create';
		const BROWSE      = 'browse';
		const UPDATE      = 'update';
		const DROP        = 'drop';
		const VIEW        = 'view';


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
		public static function can_user($method, \System\User $user)
		{
			if ($user->is_root()) {
				return true;
			}

			$cname = get_called_class();
			$conds = array();

			if (isset($cname::$access) && isset($cname::$access[$method]) && !is_null($cname::$access[$method])) {
				return !!$cname::$access[$method];
			}

			if ($user->is_guest()) {
				$conds['public'] = true;
			} else {
				$groups = $user->groups->fetch();

				if (any($groups)) {
					$conds[] = 'id_group IN ('.implode(',', collect_ids($groups)).')';
				}
			}

			$conds['trigger'] = array(
				'trigger' => 'model-'.$method,
				'trigger' => '*'
			);

			$conds['name']    = array(
				'name' => \System\Loader::get_model_from_class($cname).\System\Loader::SEP_MODEL.$method,
				'name' => '*'
			);

			$perm = get_first('System\User\Perm')->where($conds)->fetch();
			return $perm ? $perm->allow:self::get_default_for($method);
		}


		/** Ask if user has right to do this
		 * @param string      $method One of viewed, updated, dropped
		 * @param System\User $user   User to get perms for
		 * @return bool
		 */
		public function can_be($method, \System\User $user)
		{
			return self::can_user($method, $user);
		}


		public function to_object_with_perms(\System\User $user)
		{
			return array_merge($this->to_object_with_id_and_perms($user), $this->get_rels_to_object_with_perms($user));
		}


		public function to_object_with_id_and_perms(\System\User $user)
		{
			$data  = parent::to_object_with_id();
			$model = get_class($this);
			$attrs = $this::get_attr_list();

			foreach ($attrs as $attr_name) {
				if (self::is_rel($model, $attr_name)) {
					$def = $model::get_attr($attr_name);
					$rel_cname = $def['model'];
					$is_subclass = is_subclass_of($rel_cname, '\System\Model\Perm');
					$is_allowed  = $is_subclass && $rel_cname::can_user(self::BROWSE, $user);

					if (!$is_allowed) {
						unset($data[$attr_name]);

						if ($def[0] == self::REL_BELONGS_TO) {
							unset($data[self::get_belongs_to_id($model, $attr_name)]);
						}
					}
				}
			}

			return $data;
		}


		public function get_rels_to_object_with_perms(\System\User $user)
		{
			$data  = array();
			$model = get_class($this);
			$attrs = $model::get_attr_list();

			foreach ($attrs as $attr_name) {
				if (self::is_rel($model, $attr_name)) {
					$def = $model::get_attr($attr_name);
					$rel_cname = $def['model'];
					$is_subclass = is_subclass_of($rel_cname, '\System\Model\Perm');
					$is_allowed  = $is_subclass && $rel_cname::can_user(self::BROWSE, $user);

					if ($is_allowed) {
						if ($def[0] == self::REL_HAS_MANY) {
							$data[$attr_name] = $this->get_rel_has_many_ids($attr_name);
						} else if ($def[0] == self::REL_BELONGS_TO) {
							$bid = self::get_belongs_to_id($model, $attr_name);

							if ($this->$bid) {
								$data[$attr_name] = $this->$bid;
							}
						}
					}
				}
			}

			return $data;
		}


		public static function get_visible_schema(\System\User $user)
		{
			if (self::can_user(self::VIEW_SCHEMA, $user)) {
				$cname  = get_called_class();
				$schema = self::get_schema();
				$res    = array();
				$rel_attrs = array(
					'collection',
					'model'
				);

				foreach ($schema['attrs'] as $key=>$attr) {
					if (in_array($attr['type'], $rel_attrs)) {
						$rel_cname = \System\Loader::get_class_from_model($attr['model']);

						if (class_exists($rel_cname) && is_subclass_of($rel_cname, '\System\Model\Perm') && $rel_cname::can_user(self::VIEW_SCHEMA, $user)) {
							$res[] = $attr;
						}
					} else {
						$res[] = $attr;
					}
				}

				$schema['attrs'] = $res;
				return $schema;
			} else throw new \System\Error\AccessDenied();
		}
	}
}
