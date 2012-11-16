<?

namespace System\User
{
	class Perm extends \System\Model\Basic
	{
		static protected $attrs = array(
			"string"   => array('type', 'trigger'),
			"int"      => array('id_user_group', 'id_author'),
			"bool"     => array('public'),
			"datetime" => array('created_at','updated_at'),
		);

		static protected $belongs_to = array(
			"group" => array("model" => '\System\User\Group'),
		);
	}
}
