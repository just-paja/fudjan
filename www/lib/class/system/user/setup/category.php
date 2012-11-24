<?

namespace System\User\Setup
{
	class Category extends \System\Model\Database
	{
		protected static $attrs = array(
			"order" => array('int'),
			"name"  => array('varchar'),
		);


		protected static $has_many = array(
			"vars" => array("model" => '\Core\User\Setup\Variable')
		);
	}
}
