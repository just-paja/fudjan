<?

namespace System\User
{
	class Contact extends \System\Model\Basic
	{
		static protected $id_col = 'id_user_contact';
		static protected $table = 'user_contact';
		static protected $required = array();
		static protected $attrs = array(
			"int"    => array('id_user', 'id_author', 'id_contact_type'),
			"string" => array('name'),
			"bool"   => array('visible', 'deleted'),
			"datetime" => array('created_at', 'updated_at'),
		);

		static protected $belongs_to = array(
			"user" => array("model" => '\System\User'),
			"type" => array("model" => '\System\User\Contact\Type'),
		);
	}
}
