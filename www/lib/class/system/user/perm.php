<?

namespace System\User
{
	class Perm extends \System\Model\Database
	{
		static protected $attrs = array(
			"id_system_user_group" => array('int', "is_unsigned" => true),
			"id_author"     => array('int', "is_unsigned" => true),
			"type"          => array('varchar'),
			"trigger"       => array('varchar'),
			"public"        => array('bool'),
		);

		static protected $belongs_to = array(
			"group" => array("model" => '\System\User\Group'),
		);
	}
}
