<?

namespace System\User
{
	class Info extends \System\Model\Basic
	{
		static protected $id_col = 'id_user_info';
		static protected $table = 'user_info';
		static protected $required = array('content');
		static protected $attrs = array(
			"int"    => array('id_user', 'id_info_type'),
			"string" => array('content'),
			"bool"   => array('visible', 'deleted'),
			"datetime" => array('created_at', 'updated_at'),
		);

		static protected $belongs_to = array(
			"user" => array("model" => '\System\User'),
		);

		const TYPE_ABOUT  = 1;
	}
}
