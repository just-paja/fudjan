<?

namespace System\User
{
	class Setup extends \System\Model\Database
	{
		protected static $attrs = array(
			"int"     => array('id_user', 'id_user_setup_var'),
			"string"   => array('value'),
			"datetime" => array('created_at', 'updated_at'),
		);


		protected static $belongs_to = array(
			"owner" => array("model" => '\Core\User'),
			"var"   => array("model" => '\Core\User\Setup\Variable', "cols" => array('name'), "merge-model" => true),
		);


		public static function save_all($uid, array $data)
		{
			\Core\System\Query::simple_delete(self::$table, array("id_user" => intval($uid)));

			if (empty($data)) {
				return true;
			}

			$helper = new \Core\System\Query(array(
				"table" => self::$table,
				"cols"  => self::$attrs,
			));

			$date = new \DateTime();

			foreach ($data as $vid=>$d) {
				foreach ((array) $d as $v) {
					$helper->add_insert_data(array(
						"id_user" => intval($uid),
						"id_user_setup_var" => intval($vid),
						"value" => $v,
						"created_at" => &$date,
						"updated_at" => &$date
					));
				}
			}

			return !!$helper->insert();
		}


		public static function get_for_user($uid)
		{
			$setup = array();

			$helper = get_all("\Core\User\Setup", array("id_user" => intval($uid)), array());
			$helper->reset_cols();
			$helper->assoc_with_no_model();
			$helper->add_cols(array('id_user_setup_var', 'value'), "t0");
			$res = $helper->fetch();

			foreach ($res as $set) {
				if (!isset($setup[$set['id_user_setup_var']])) {
					$setup[$set['id_user_setup_var']] = array();
				}

				$setup[$set['id_user_setup_var']][] = $set['value'];
			}

			return $setup;
		}
	}
}
