<?

namespace Helper\Database
{
	class Attr extends \System\Model\Attr
	{
		protected static $attrs = array(
			"name"        => array('varchar'),
			"type"        => array('varchar'),
			"default"     => array('varchar'),
			"length"      => array('varchar'),
			"is_primary"  => array('bool'),
			"is_unique"   => array('bool'),
			"is_null"     => array('bool'),
			"is_unsigned" => array('bool'),
			"is_index"    => array('bool'),
			"is_autoincrement" => array('bool'),
		);

		protected static $default_cols = array(
			"created_at" => array('datetime', "default" => 0),
			"updated_at" => array('datetime', "default" => 0),
		);

		protected static $allowed_types = array(
			'bool', 'int', 'int_set', 'float', 'varchar', 'text', 'html', 'password', 'file', 'image', 'video_youtube', 'date', 'time', 'datetime', 'json', 'url', 'email', 'point',
		);


		/** Get all attributes from model name
		 * @param string $model
		 */
		public static function get_from_model($model)
		{
			$model::check_model();
			$result = array();
			$attrs  = $model::get_attr_def();
			$relations = \Helper\Database\Relation::get_from_model($model);
			$id_col = $model::get_id_col($model);

			$result[$id_col] = self::from_def($id_col, array(
				"type"             => 'int',
				"is_unsigned"      => true,
				"is_primary"       => true,
				"is_autoincrement" => true,
			));

			foreach ($attrs as $name => $def) {
				if (!$model::is_rel($name)) {
					$result[$name] = self::from_def($name, $def);
				}
			}

			foreach (self::$default_cols as $name => $def) {
				$result[$name] = self::from_def($name, $def);
			}

			return $result;
		}


		/** Create attr instance from definition
		 * @param string $name
		 * @param array  $definition
		 */
		public static function from_def($name, array $def)
		{
			if (!isset($def['type']) && isset($def[0])) {
				$def['type'] = $def[0];
			}

			if (!in_array($def['type'], self::$allowed_types)) {
				throw new \System\Error\Wtf(sprintf("Unknown attribute type: %s", $def['type']));
			}

			$def['name'] = $name;
			$def['is_null'] = !empty($def['is_null']);
			$def['is_unique'] = !empty($def['is_unique']);
			$def['is_unsigned'] = !empty($def['is_unsigned']);

			return new self($def);
		}
	}
}
