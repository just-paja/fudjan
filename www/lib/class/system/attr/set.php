<?

namespace System\Attr
{
	class Set extends \System\Model\Database
	{
		static protected $id_col = 'id_attr_set';
		static protected $table = 'attr-set';
		static protected $required_attrs = array('name', 'object_class');

		static protected $attrs = array(
			"int"      => array('id_attr_set'),
			"string"   => array('name', 'object_class', 'object_type'),
			"bool"     => array('visible'),
			"datetime" => array('created_at','updated_at')
		);

		static protected $has_many = array(
			"attrs" => array("model" => '\Core\Attr'),
			"groups" => array("model" => '\Core\Attr\Group'),
		);

		function count_attrs()
		{
			return count_all("\Core\Attr", array(self::$id_col => $this->id));
		}
	}
}
