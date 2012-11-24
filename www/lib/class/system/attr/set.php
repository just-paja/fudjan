<?

namespace System\Attr
{
	class Set
	{
		static protected $attrs = array(
			"id_attr_set"  => array('int', "is_unsigned" => true),
			"name"         => array('varchar'),
			"object_class" => array('varchar'),
			"object_type"  => array('varchar'),
			"visible"      => array('bool'),
		);

		static protected $has_many = array(
			"attrs" => array("model" => '\System\Attr'),
			"groups" => array("model" => '\System\Attr\Group'),
		);

		function count_attrs()
		{
			return count_all("\Core\Attr", array(self::$id_col => $this->id));
		}
	}
}
