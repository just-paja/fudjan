<?

namespace System\User\Setup
{
	class Category extends \System\Model\Basic
	{
		protected static $attrs = array(
			"int"      => array('order'),
			"string"   => array('name'),
			"datetime" => array('created_at', 'updated_at'),
		);


		protected static $has_many = array(
			"vars" => array("model" => '\Core\User\Setup\Variable')
		);
	}
}
