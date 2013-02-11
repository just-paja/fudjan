<?

namespace System\Model
{
	abstract class Ext extends Database
	{

		static $attr_cache = array();
		static private $attr_name_cache = array();
		static private $object_attr_types = array(
			"tag-single" => '\Core\Tag',
			"tag-multi" => '\Core\Tag'
		);

		private $ext = array();
		private $ext_attr_status = array();

		static function get_all($model, array $conds = array(), array $opts = array(), array $joins = array())
		{
			self::clear_name($model);
			if (!$model || !class_exists($model)) throw new \FatalException(sprintf(_('Model not found: %s'), var_export($model, true)));
			if (!in_array("System\ExtModel", class_parents($model)) || !any($attrs = self::get_model_attrs($model))) {
				return parent::get_all($model, $conds, $opts, $joins);
			}

			$helper = parent::get_all($model, $conds, $opts, $joins);
			$helper->join('attr-set', "ON(tas.`object_class` = '".self::clear_model_name($model)."')", 'tas');

			foreach (self::get_model_attrs($model) as $attr) {
				$tal = 'tav_'.$attr->id;

				switch ($attr->type) {
					case 'tag-multi':
						$helper->add_cols(array($attr->seoname => "
							CONCAT('#Array[', IFNULL((SELECT GROUP_CONCAT(`id_tag` SEPARATOR ',') FROM `tag-content`), ''), ']')
						"), false);
						break;

					default:
						$jstr = " AND IFNULL(`".$tal."`.`object_id` = `".$tal."`.`object_id`, true)";

						$helper->left_join('attr', "ON(tas.id_attr_set = ta_".$tal.".id_attr_set AND ta_".$tal.".id_attr = ".intval($attr->id).")", 'ta_'.$tal);
						$helper->left_join('attr-value', "ON(
							ta_".$tal.".id_attr = ".$tal.".id_attr
							AND ".$tal.".id_attr = ".intval($attr->id).$jstr."
							AND ".$tal.".object_id = `t0`.".$model::$id_col."
						)", $tal);
						$helper->add_cols(array($attr->seoname => 'value_'.self::get_data_origin($attr->type)), $tal);
						break;
				}
			}

			return $helper;
		}


		static function get_first($model, array $conds = array(), array $opts = array())
		{
			$opts['limit'] = 1;
			$opts['first'] = true;

			return self::get_all($model, $conds, $opts);
		}


		static function find($model, $ids = NULL, $force_array = false)
		{
			if (is_array($ids) || ($ex = strpos($ids, ','))) {

				$ex && $ids = explode(',', $ids);
				$conds = array(Database::get_id_col($model). " IN ('" .implode('\',\'', $ids)."')");
				return self::get_all($model, $conds)->fetch();

			} else {

				$col = Database::get_id_col($model);
				if (!is_numeric($ids)) {
					if (self::attr_exists($model, 'seoname')) {
						$col = 'seoname';
					} else {
						$ids = intval(substr($ids, strlen($ids) - strpos(strrev($ids), '-')));
					}
				}

				$conds = array($col => $ids);
				$result = self::get_first($model, $conds)->fetch();

				return $force_array ? array($result):$result;
			}
		}


		public function get_data()
		{
			return array_merge($this->data, $this->ext);
		}


		public function get_core_data()
		{
			return parent::get_data();
		}


		public function extend(&$attrs)
		{
			$this->ext = (array) $attrs;
		}


		public function __get($attr)
		{
			$model = get_class($this);
			if (in_array($attr, self::get_model_attr_names($model))) {
				// TODO: convert to cache
				def(self::$attr_cache[$model.'-obj-types'], array());

				if (array_key_exists($attr, self::$attr_cache[$model.'-obj-types']) && !$this->ext_attr_status['fetched-'.$attr]) {
					$oattr = &self::$attr_cache[$model.'-obj-types'][$attr];
					$class = self::$object_attr_types[$oattr->type];
					if (!is_array($this->ext[$attr])) {
						$vals = array(&$this->ext[$attr]);
					} else {
						$vals = &$this->ext[$attr];
					}

					foreach ($vals as &$val) {
						if ($val) {
							$val = find($class, $val);
						}
					}
					$this->ext_attr_status['fetched-'.$attr] = true;
				} else {
					return $this->ext[$attr];
				}
			} else {
				return parent::__get($attr);
			 }
		}


		public function __set($attr, $value)
		{
			$model = get_class($this);
			in_array($attr, self::get_model_attrs($model)) ?
				 $this->ext[$attr]:parent::__set($attr, $value);

			return $this;
		}


		public static function get_model_attrs($model)
		{
			if (is_object($model)) {
				$model = get_class($model);
			}

			if (!is_array(Cache::fetch('extmodel-attrs-'.$model, $attrs))) {
				if ($set = Cache::get('extmodel-attr-set-'.$model)) {
					$attrs = get_all("\Core\Attr", array("id_attr_set" => $set->id))->fetch();
				} else {
					$helper = Database::get_all("\Core\Attr\Set", array("`object_class` = '".self::clear_model_name($model)."'"), array());
					$helper->join('attr', "USING(id_attr_set)", 'ta');
					$helper->add_cols(array_merge((array) Database::get_id_col("\Core\Attr"), Database::get_model_attrs("\Core\Attr")), 'ta');
					$attrs = $helper->assoc_with("\Core\Attr")->fetch();
				}

				$types = array();
				foreach ($attrs as $attr) {
					if (array_key_exists($attr->type, self::$object_attr_types)) {
						$types[$attr->seoname] = $attr;
					}
				}

				Cache::set('extmodel-attrs-'.$model, $attrs);
				Cache::set('extmodel-attr-types-'.$model, $types);
			}

			return $attrs;
		}


		public static function get_model_attr_names($model)
		{
			if (is_object($model)) {
				$model = get_class($model);
			}

			if (!Cache::fetch('extmodel-attr-names-'.$model, $attr_names)) {

				$attr_names = array();
				foreach (self::get_model_attrs($model) as $attr) {
					$attr_names[] = $attr->seoname;
				}
			}

			return $attr_names;
		}


		public static function get_model_attr_groups($model)
		{
			if (is_object($model)) {
				$model = get_class($model);
			}

			if (!Cache::fetch('groups-'.$model, $groups) && self::get_model_attr_set($model)) {
				$groups = Cache::set('groups-'.$model, Database::get_all("\Core\Attr\Group", array("id_attr_set" => self::get_model_attr_set($model)->id))->fetch());
			} else {
				$groups = array();
			}

			return $groups;
		}


		public static function get_model_attr_set($model)
		{
			if (is_object($model)) {
				$model = get_class($model);
			}

			if (!Cache::fetch('extmodel-attr-set-'.$model, $set)) {
				Cache::set('extmodel-attr-set-'.$model, $set = get_first("\Core\Attr\Set", array("object_class" => self::clear_model_name($model)))->fetch());
			}

			return $set;
		}


		public static function clear_name($name)
		{
			return strtolower($name);
		}


		public static function get_data_origin($attr_type)
		{
			switch ($attr_type) {
				case 'datetime':
					return 'date';
				case 'image':
					return 'string';
				case 'tag-single':
					return 'int';
				case 'tag-multi':
					return NULL;
				default:
					return $attr_type;
			}
		}


		public function update_attrs(array $update)
		{
			foreach ($attrs = self::get_model_attrs($this) as $attr)
			{
				$this->ext[$attr->seoname] = def($update[$attr->seoname]);

				if (strpos($this->ext[$attr->seoname], '#Array') === 0) {
					$this->ext[$attr->seoname] = json_decode(substr($this->ext[$attr->seoname], 6), true);
				}

				switch ($attr->type) {

					case 'tag-single':
						$t = &$this->ext[$attr->seoname];
						if ($t) {
							$t = find("\Core\Tag", $t);
						}
						break;

				}

				Database::fix_datatypes(array($attr->seoname), $attr->type, $this->ext);
				unset($update[$attr->seoname]);
			}

			return parent::update_attrs($update);
		}


		public function save()
		{

			if ($this->id) {
				\Dibi::query("
					DELETE `attr-value` FROM `attr-value` INNER JOIN `attr` INNER JOIN `attr-set`
						WHERE
							`attr-value`.`id_attr` = `attr`.`id_attr`
							AND `attr`.`id_attr_set` = `attr-set`.`id_attr_set`
							AND `attr-value`.`object_id` = ".intval($this->id)."
							AND `attr-set`.`object_class` = '".(self::clear_model_name(get_class($this)))."'
				");
			}

			$ext = $this->ext;
			$bm_save = parent::save();
			$this->ext = $ext;

			$helper = new Query(array(
				"table" => 'attr-value',
				"cols" => array('id_attr', 'object_id', 'value_string', 'value_text', 'value_int', 'value_float', 'value_datetime', 'created_at', 'updated_at'),
			));
			$go = false;

			foreach (self::get_model_attrs($this) as $attr) {
				if ($val = $this->ext[$attr->seoname]) {

					if (is_object($val)) {
						switch (strtolower(get_class($val))) {
							case 'core\image':
								$val->save();
								break;

							case 'core\tag':
								$val = $val->id;
								break;
						}
					}

					if ($origin = self::get_data_origin($attr->type)) {
						$helper->add_insert_data(array(
							"id_attr" => $attr->id,
							"object_id" => $this->id,
							"value_".$origin => $val,
						));
						$go = true;
					} else switch($attr->type) {
						case 'tag-multi':
							\Core\Tag::dessoc($this, $attr->id);
							\Core\Tag::assoc($val, $this, $attr->id);
							break;
					}
				}
			}

			$go && $helper->insert();
			return $this;
		}


		public static function get_available_classes()
		{
			$files = \Core\Utils::get_all_files(ROOT.'/'.DIR_CLASS);
			foreach ($files as $file) {
				if (is_file($file) && strpos($file, '.') !== 0 && strpos($file, '.php') > 0) {
					$stream = file($file);
					$scope  = explode('/', substr($file, strlen(ROOT.'/'.DIR_CLASS.'/')));
					array_pop($scope);

					foreach ($stream as $line) {
						if (strpos($line, 'class') === 0) {
							if (strpos($line, 'ExtModel') !== false) {
								$scope[] = substr($line, strpos($line, 'class')+6, strpos($line, 'extends')-7);
								$classes[] = implode('::', array_map('ucfirst', $scope));
							} else {
								break;
							}
						}
					}
				}
			}

			return $classes;
		}


		public static function clear_model_name($name)
		{
			$name = str_replace("\\", '::', $name);
			if (strpos($name, '::') === 0) {
				$name = substr($name, 2);
			}
			return $name;
		}
	}
}
