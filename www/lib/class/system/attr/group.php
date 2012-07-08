<?

namespace System\Attr
{
	class Group extends \System\Model\Basic
	{

		static protected $id_col = 'id_attr_group';
		static protected $table = 'attr-group';
		static protected $required_attrs = array('name', 'id_attr_set');

		static protected $attrs = array(
			"int"      => array('id_attr_set', 'order'),
			"string"   => array('name'),
			"bool"     => array('visible'),
			"datetime" => array('created_at','updated_at')
		);

		static protected $belongs_to = array(
			"set" => array("model" => '\Core\Attr\Set'),
		);

		static protected $has_many = array(
			"ext_attrs" => array("model" => '\Core\Attr'),
		);


		function count_attrs()
		{
			return count_all("\Core\Attr", array(self::$id_col => $this->id));
		}
	}
}
