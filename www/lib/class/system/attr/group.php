<?

namespace System\Attr
{
	class Group
	{
		static protected $attrs = array(
			"id_attr_set" => array('int', "is_unsigned" => true),
			"order"       => array('int', "is_unsigned" => true),
			"name"        => array('varchar'),
			"visible"     => array('bool', "default" => false),
		);

		static protected $belongs_to = array(
			"set" => array("model" => '\System\Attr\Set'),
		);

		static protected $has_many = array(
			"ext_attrs" => array("model" => '\System\Attr'),
		);


		function count_attrs()
		{
			return count_all("\Core\Attr", array(self::$id_col => $this->id));
		}
	}
}
