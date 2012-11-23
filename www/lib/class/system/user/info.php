<?

namespace System\User
{
	class Info extends \System\Model\Database
	{
		static protected $attrs = array(
			"id_user"      => array('int', "is_unsigned" => true),
			"id_info_type" => array('int', "is_unsigned" => true),
			"content"      => array('varchar'),
			"visible"      => array('bool'),
			"deleted"      => array('bool'),
		);

		static protected $belongs_to = array(
			"user" => array("model" => '\System\User'),
		);

		const TYPE_ABOUT  = 1;
	}
}
