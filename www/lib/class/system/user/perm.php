<?

namespace System\User
{
	class Perm extends \System\Model\Database
	{
		static protected $attrs = array(
			"type"          => array('varchar'),
			"trigger"       => array('varchar'),
			"id_user_group" => array('int', "is_unsigned" => true),
			"id_author"     => array('int', "is_unsigned" => true),
			"public"        => array('bool'),
		);

		static protected $belongs_to = array(
			"group" => array("model" => '\System\User\Group'),
		);
	}
}
