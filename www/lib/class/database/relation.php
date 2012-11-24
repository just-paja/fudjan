<?

namespace Database
{
	class Relation extends \System\Model\Attr
	{
		protected static $attrs = array(
			"name"      => array('varchar'),
			"type"      => array('varchar'),
			"model"     => array('varchar'),
			"parent"    => array('varchar'),
			"is_master" => array('bool'),
		);


		protected static $allowed_types = array(
			'has_one', 'belongs_to', 'has_many',
		);

		private $bilinear_rel;

		public static function get_from_model($model)
		{
			$relations = $model::get_relation_def($model);
			$models = array();

			foreach ($relations as $name=>$def) {
				$models[$name] = self::from_def($model, $name, $def);
			}

			return $models;
		}


		public static function from_def($parent, $name, array $def)
		{
			$def['parent'] = $parent;
			$def['name'] = $name;
			def($def['is_master'], false);

			if (strpos($def['model'], '\\') === 0) {
				$def['model'] = substr($def['model'], 1);
			}

			return new self($def);
		}


		public function is_bilinear()
		{
			$relations = self::get_from_model($this->model);
			foreach ($relations as $rel) {
				if ($this->is_bilinear_with($rel)) {
					$this->bilinear_rel = $rel;

					if (!$this->bilinear_rel->is_master) {
						$this->is_master = true;
					}

					return true;
				}
			}

			return false;
		}


		public function is_bilinear_with(self $rel)
		{
			return $rel->model == $this->parent;
		}


		public function get_bilinear_table_name()
		{
			$name = array();

			if ($this->is_master && !$this->bilinear_rel->is_master) {
				$name['master'] = \System\Model\Database::get_table($this->parent);
				$name['slave']  = \System\Model\Database::get_table($this->model);
			} else {
				$name['master'] = \System\Model\Database::get_table($this->model);
				$name['slave']  = \System\Model\Database::get_table($this->parent);
			}

			return implode('_has_', $name);
		}


	}
}
