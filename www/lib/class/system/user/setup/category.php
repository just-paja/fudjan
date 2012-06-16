<?

namespace Core\User\Setup;

class Category extends \Core\System\BasicModel
{

	protected static $table = 'user-setup-category';
	protected static $id_col = 'id_user_setup_category';
	protected static $attrs = array(
		"int"      => array('order'),
		"string"   => array('name'),
		"datetime" => array('created_at', 'updated_at'),
	);
	
	
	protected static $has_many = array(
		"vars" => array("model" => '\Core\User\Setup\Variable')
	);
}
