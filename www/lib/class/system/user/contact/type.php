<?

namespace System\User\Contact
{
	class Type extends \System\Model\Basic
	{
		static protected $id_col = 'id_user_contact_type';
		static protected $table = 'user_contact_type';
		static protected $required = array();
		static protected $attrs = array(
			"string" => array('name'),
			"bool"   => array('visible', 'deleted'),
			"datetime" => array('created_at', 'updated_at')
		);

		static protected $has_many = array(
			"contacts" => array("model" => '\Core\User\Contact'),
		);
	}
}
