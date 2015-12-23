<?php

namespace Helper\Database
{
	class Relation extends \System\Model\Attr
	{
		protected static $attrs = array(
			"name"        => array("type" => 'varchar'),
			"type"        => array("type" => 'varchar'),
			"model"       => array("type" => 'varchar'),
			"parent"      => array("type" => 'varchar'),
			"is_null"     => array("type" => 'bool'),
			"is_master"   => array("type" => 'bool'),
			"is_bilinear" => array("type" => 'bool'),
			"is_natural"  => array("type" => 'bool'),
		);


		protected static $allowed_types = array(
			\System\Model\Database::REL_BELONGS_TO,
			\System\Model\Database::REL_HAS_MANY,
			\System\Model\Database::REL_HAS_ONE
		);

		private $bilinear_rel;


		public static function get_from_model($model)
		{
			$model::check_model();
			$relations = $model::get_model_relations();
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
			if ($this->is_bilinear) {
				$this->get_bilinear_rel();
			}

			return $this->is_bilinear;
		}


		public function get_bilinear_rel()
		{
			$relations = self::get_from_model($this->model);

			foreach ($relations as $rel) {
				if ($this->is_bilinear_with($rel)) {
					$this->bilinear_rel = $rel;

					if (!$this->bilinear_rel->is_master) {
						$this->is_master = true;
					}
				}
			}

			return $this->bilinear_rel;
		}


		public function is_bilinear_with(self $rel)
		{
			return $rel->model == $this->parent;
		}


		public function get_bilinear_table_name()
		{
			$name = array();
			$mp = $this->parent;
			$mm = $this->model;

			if ($this->is_master) {
				$name['master'] = $mp::get_table();
				$name['slave']  = $mm::get_table();
			} else {
				$name['master'] = $mm::get_table();
				$name['slave']  = $mp::get_table();
			}

			return implode('_has_', $name);
		}
	}
}
