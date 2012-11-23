<?

namespace Database
{
	class Attr extends \System\Model\Attr
	{
		protected static $attrs = array(
			"name"      => array('varchar'),
			"type"      => array('varchar'),
			"default"   => array('varchar'),
			"length"    => array('varchar'),
			"is_unique" => array('bool'),
			"is_null"   => array('bool'),
		);

		protected static $allowed_types = array(
			'bool', 'int', 'float', 'varchar', 'text', 'password', 'image', 'datetime'
		);


		public static function get_from_model($model)
		{
			$result = array();
			$attrs  = $model::get_attr_def($model);

			foreach ($attrs as $name => $def) {
				$attr = self::from_def($name, $def);
				$result[$name] = $attr;
			}

			return $result;
		}


		public static function from_def($name, array $def)
		{
			$def['type'] = $def[0];
			$def['name'] = $name;
			$def['is_null']   = !empty($def['is_null']);
			$def['is_unique'] = !empty($def['is_unique']);

			return new self($def);
		}
	}
}
