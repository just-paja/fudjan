<?

/** Database data model handling */
namespace System\Model
{
	/**
	 * Simple database model that manages attributes, fetching and saving
	 * data and also database relations: belongs_to, has_one, has_many. Billinear
	 * has_many relation is also supported
	 *
	 * @package system
	 * @subpackage models
	 * @property string $table asdfasdf
	 * @property string $id_col
	 * @property array  $tables_generated
	 * @property array  $id_cols_generated
	 * @property array  $relation_types
	 */
	abstract class Database extends Callback
	{
		const REL_BELONGS_TO = 'belongs_to';
		const REL_HAS_ONE    = 'has_one';
		const REL_HAS_MANY   = 'has_many';

		const ALLOW_RELATION_DELETE = false;

		/** string Model table name */
		static protected $table;

		/** Mode ID column name */
		static protected $id_col;

		/** Resolved model tables */
		static protected $resolved_tables = array();

		/** Resolved model primary key names */
		static protected $resolved_ids = array();

		/** Table names generated by model name */
		static protected $tables_generated = array();

		/** ID columns generated by model name */
		static protected $id_cols_generated = array();

		/** Allowed model relation types */
		static protected $relation_types = array(self::REL_BELONGS_TO, self::REL_HAS_ONE, self::REL_HAS_MANY);

		static protected $conversions = null;

		static protected $required = array();

		static private $models_checked = array();


		/** Detault conditions for get_all */
		static private $quick_conds = array(
			"visible" => true,
			"deleted" => false,
			"used"    => true,
		);


		protected $relations = array();

		public $is_new_object = false;


		/**
		 * Get name of associated table
		 *
		 * @return string
		 */
		public static function get_table()
		{
			if (!empty(static::$table)) {
				return static::$table;
			}

			$model = get_called_class();

			if (empty(static::$resolved_tables[$model])) {
				static::$resolved_tables[$model] = implode('_', array_map('strtolower', array_filter(explode('\\', $model))));
			}

			return static::$resolved_tables[$model];
		}


		/**
		 * Check if model is valid before operating
		 *
		 * @return void
		 */
		public static function check_model()
		{
			$model = get_called_class();

			if (empty(static::$resolved_models[$model])) {
				parent::check_model();
				$idc = static::get_id_col();

				if (!array_key_exists($idc, static::$attrs)) {
					static::add_attr($idc, array(
						"type"        => 'int',
						"is_unsigned" => true,
						"is_primary"  => true,
						"is_autoincrement" => true
					));
				}

				if (!static::has_attr('created_at')) {
					static::add_attr('created_at', array(
						"type" => 'datetime',
						"default" => 'NOW()'
					));
				}

				if (!static::has_attr('updated_at')) {
					static::add_attr('updated_at', array(
						"type" => 'datetime'
					));
				}

				$model::check_relations();
			}
		}


		/**
		 * Update relation attributes
		 *
		 * @return void
		 */
		public static function check_relations()
		{
			$model = get_called_class();

			foreach ($model::$attrs as $attr=>$def) {
				if ($model::get_attr_type($attr) == self::REL_BELONGS_TO) {
					$rel_attr_name = $model::get_belongs_to_id($attr);
					static::add_attr($rel_attr_name, self::get_default_belongs_to_def($rel_attr_name, $def));
				}
			}
		}


		/**
		 * Override settings of belongs to relation to real database options
		 *
		 * @param string $name
		 * @param array  $def
		 * @return array
		 */
		public static function get_default_belongs_to_def($name, $def)
		{
			return array_merge($def, array(
				"is_unsigned" => true,
				"is_index" => true,
				"is_fake" => true,
				"is_generated" => true,
				"rel" => $name,
				"type" => 'int'
			));
		}


		/**
		 * Get table column for belongs_to relation foreign key in this model
		 *
		 * @param string $attr
		 * @return string
		 */
		public static function get_belongs_to_id($attr)
		{
			$model = get_called_class();

			if ($model::get_attr_type($attr) == self::REL_BELONGS_TO) {
				$def = $model::get_attr($attr);

				if (any($def['foreign_key'])) {
					return $def['foreign_key'];
				} else if (empty($def['is_natural'])) {
					return "id_".$attr;
				}

				$rel = $def['model'];
				return $rel::get_id_col();
			}

			throw new \System\Error\Model(sprintf('Attribute "%s" of model "%s" is not belongs_to relation.', $model, $attr));
		}


		/**
		 * Get name of ID column
		 *
		 * @return string
		 */
		public static function get_id_col()
		{
			if (!empty(static::$id_col)) {
				return static::$id_col;
			}

			$model = get_called_class();

			if (empty(static::$resolved_ids[$model])) {
				static::$resolved_ids[$model] = 'id_'.static::get_table();
			}

			return static::$resolved_ids[$model];
		}


		/**
		 * Create new object of model and save it
		 *
		 * @param array  $attrs Attribute data
		 * @return object
		 */
		public static function create(array $attrs)
		{
			$model = get_called_class();
			$obj = new $model($attrs);
			return $obj->save();
		}


		/**
		 * Get all models of a class
		 *
		 * @param array $conds Set of conditions
		 * @param array $opts  Set of query options
		 * @param array $joins Set of query joins
		 * @return array Set of matched models
		 */
		public static function get_all(array $conds = array(), array $opts = array())
		{
			$model = get_called_class();
			$model::check_model();

			return new \System\Database\Query(
				array(
					"table" => $model::get_table(),
					"cols"  => $model::get_attr_list_sql(),
					"opts"  => $opts,
					"conds" => $conds,
					"model" => $model,
				)
			);
		}


		/**
		 * Find model by ID or set of IDs
		 *
		 * @param int|array $ids        ID or list of IDs to look for
		 * @param bool      $force_list Returns array even if single result
		 * @return self|array
		 */
		public static function find($ids = NULL, $force_list = false)
		{
			$model = get_called_class();

			if (is_array($ids) || ($ex = strpos($ids, ','))) {

				any($ex) && $ids = explode(',', $ids);
				$conds = array($model::get_id_col(). " IN ('" .implode('\',\'', $ids)."')");
				return $model::get_all($conds)->fetch();

			} else {

				$col = $model::get_id_col();
				if (!is_numeric($ids)) {
					if ($model::has_attr('seoname')) {
						$col = 'seoname';
					} else {
						$ids = \System\Url::get_seoid($ids);
					}
				}

				$conds = array($col => $ids);
				$result = $model::get_first($conds)->fetch();

				return $force_list ? array($result):$result;
			}
		}


		/**
		 * Get first instance of matched query
		 *
		 * @param array $conds  Set of conditions
		 * @param array $opts   Set of query options
		 * @return BasicModel
		 */
		public static function get_first(array $conds = array(), array $opts = array())
		{
			$model = get_called_class();
			$opts['limit'] = 1;
			$opts['first'] = true;

			return $model::get_all($conds, $opts);
		}


		/**
		 * Count all
		 *
		 * @param array $conds  Set of conditions
		 * @param array $opts   Set of query options
		 * @return int
		 */
		public static function count_all(array $conds = array(), array $opts = array())
		{
			$model = get_called_class();
			return $model::get_all($conds, $opts)->count();
		}


		/**
		 * Is model attribute of type relation?
		 *
		 * @param mixed  $model Class or instance of desired model
		 * @param string $attr  Name of atribute
		 * @return bool
		 */
		public static function is_rel($attr)
		{
			return in_array(static::get_attr_type($attr), self::$relation_types);
		}


		/**
		 * Attribute getter
		 *
		 * @param string $attr
		 * @return mixed
		 */
		public function __get($attr)
		{
			if ($this::has_attr($attr) && $this::is_rel($attr)) {
				return $this->get_rel($attr);
			} else {
				if ($attr == 'id' || $attr == $this::get_id_col()) {
					return def($this->data[$this::get_id_col()], 0);
				}

				return parent::__get($attr);
			}
		}

		/**
		 * Public attribute handler, setter override for database model. Handles
		 * extra relations.
		 *
		 * @param string $name  Attribute handler
		 * @param mixed  $value Value to be set
		 * @return BasicModel
		 * @see \System\Model\Attr::__set()
		 */
		public function __set($attr, $value)
		{
			if ($this::has_attr($attr) && $this::is_rel($attr)) {
				$type = $this::get_attr_type($attr);

				if ($type == self::REL_HAS_MANY) {
					$this->set_rel_has_many($attr, $value);
				} else {
					$this->set_rel_single_value($attr, $value);
				}

				return $this;
			}

			if ($attr == 'id' || $attr == $this::get_id_col()) {
				$this->data[$this::get_id_col()] = intval($value);
			}

			return parent::__set($attr, $value);
		}


		/**
		 * Set has_many relation value
		 *
		 * @param string $attr
		 * @param array  $value
		 * @return System\Model\Database
		 */
		protected function set_rel_has_many($attr, array $value)
		{
			$invalid = false;

			if (empty($value)) {
				$value = array();
			}

			$corrected = array();
			$model = get_model($this);
			$def = $model::get_attr($attr);

			foreach ($value as $val) {
				if (is_object($val)) {
					$corrected[] = $val;
				} else {
					if (gettype($val) == 'string') {
						$val = intval($val);
					}

					if (gettype($val) == 'integer' && $val > 0) {
						$corrected[] = $val;
					} else {
						$invalid = true;
						break;
					}
				}
			}

			if ($invalid) {
				if (is_object($val)) {
					$report_val = 'Instance of '.get_model($value);
				} else {
					$report_val = gettype($val);
				}

				throw new \System\Error\Model(sprintf(
					"Invalid value was given to has_many relation '%s' of model '%s'. Values must be instances of '%s' or int greater than zero. '%s' was given.",
					$attr, $model, $def['model'], $report_val
				));
			}

			$this->relations[$attr] = $corrected;
			return $this;
		}


		/**
		 * Sets value for relations that lead to single object. Called internally by
		 *  __set on belongs_to and has_one relations
		 *
		 * @param string                 $name  Attribute name
		 * @param \System\Model\Database $value Value object
		 * @return $this
		 */
		protected function set_rel_single_value($name, $value)
		{
			$model = get_model($this);
			$type = $model::get_attr_type($name);
			$def = $model::get_attr($name);
			$value = empty($value) ? null:$value;
			$is_null = !empty($def['is_null']) && is_null($value);
			$rel_model = $def['model'];

			if (is_numeric($value)) {
				$val = $rel_model::find($value);

				if ($val) {
					$value = $val;
				} else throw new \System\Error\Model(
						"Object not found.",
						sprintf("Integer value '%s' was given to attribute '%s' of model '%s' but related object of model '%s' was not found.", $value, $name, $model, $def['model'])
					);
			}

			if ($type == self::REL_BELONGS_TO || $type == self::REL_HAS_ONE) {
				if (is_object($value) || $is_null) {
					if (($value instanceof $rel_model) || $is_null) {
						$this->relations[$name] = $value;

						if ($type == self::REL_BELONGS_TO) {
							$idc = $model::get_belongs_to_id($name);
							$this->$idc = $is_null ? null:$value->id;
						}

					} else throw new \System\Error\Argument(sprintf(
						"Value for attr '%s' of model '%s' must be instance of '%s' by definition. Instance of '%s' was given.",
						$name, $model, $rel_model, get_model($value)
					));
				} else throw new \System\Error\Argument(sprintf(
					"Value for attr '%s' of model '%s' which is '%s' relation must be object that inherits System\Model\Database. '%s' was given.",
					$name, $model, $type, gettype($value)
				));
			}

			return $this;
		}



		/**
		 * Relation getter for all supported relation types.
		 * @param   string $rel   Name of the relation
		 * @return null|System\Model\Database|System\Database\Query
		 */
		protected function get_rel($rel)
		{
			if ($this::is_rel($rel)) {
				$type = $this::get_attr_type($rel);

				if ($type == self::REL_HAS_MANY) {
					return $this->get_rel_has_many($rel);
				} elseif ($type == self::REL_HAS_ONE) {
					return $this->get_rel_has_one($rel);
				} elseif ($type == self::REL_BELONGS_TO) {
					return $this->get_rel_belongs_to($rel);
				} else throw new \System\Error\Argument(sprintf(
					"Attribute '%s' of model '%s' is not a relation of any known type.",
					get_class($this), $rel
				));
			} throw new \System\Error\Argument(sprintf(
				"Attribute '%s' of model '%s' is not a relation of any kind.",
				get_class($this), $rel
			));
		}


		/**
		 * Relation getter for has_many relations. Returns query object
		 *
		 * @param string $rel Relation name
		 * @return System\Database\Query
		 */
		protected function get_rel_has_many($rel)
		{
			$model = get_model($this);
			$rel_attrs = $this::get_attr($rel);
			$rel_model = $rel_attrs['model'];
			$join_alias = 't0';
			$helper = $rel_model::get_all();
			$rel_model::check_model();

			if (any($rel_attrs['is_bilinear'])) {
				$join_alias = 't_'.$rel;
				$table_name = $this::get_bilinear_table_name($rel_attrs);
				$using = $rel_model::get_id_col();

				$helper->join($table_name, "USING(".$using.")", $join_alias);
				$idc = any($rel_attrs['foreign_name']) ? $rel_attrs['foreign_name']:$this::get_id_col();
			} else {
				if ($foreign = $model::get_rel_bound_to($rel)) {
					$idc = $rel_model::get_belongs_to_id($foreign);
				} else throw new \System\Error\Model(
					"Could not find model relation.",
					sprintf("There is no relation between '%s::%s' (has_many) and model %s (belongs_to)", $model, $rel, $rel_attrs['model'])
				);
			}

			$rel_model::has_attr('order') && $helper->add_opts(array("order-by" => "`t0`.".'`order` ASC'));

			$helper->where(array($idc => $this->id), $join_alias);
			$helper->assoc_with($rel_model);

			$this->id ? $helper->cancel_ignore():$helper->ignore_query(array());
			return $helper;
		}


		/**
		 * Get IDs of all object attached via has_many relation
		 *
		 * @param string $rel Relation attr name
		 * @return array
		 */
		protected function get_rel_has_many_ids($rel)
		{
			$def = $this::get_attr($rel);
			$rel_model = $def['model'];
			$idc = $rel_model::get_id_col();
			$data = $this->$rel->reset_cols()->add_cols($idc, 't0')->assoc_with(null)->fetch();
			$ids = array();

			foreach ($data as $row) {
				$ids[] = intval($row[$idc]);
			}

			return $ids;
		}


		/**
		 * Relation getter for has_one relations. Saves the value inside this object
		 * and returns the value.
		 *
		 * @param string $rel Relation attr name
		 * @return null|object
		 */
		protected function get_rel_has_one($rel)
		{
			if (empty($this->relations[$rel])) {
				$rel_attrs = $this::get_attr($rel);
				$bound = $this::get_rel_bound_to($rel);

				if (any($rel_attrs['foreign_key'])) {
					$idc = $rel_attrs['foreign_key'];
				} else {
					if (any($rel_attrs['foreign_name'])) {
						$idc = 'id_'.$rel_attrs['foreign_name'];
					} else {
						$idc = 'id_'.$bound;
					}
				}

				$conds = array($idc => $this->id);

				if (isset($rel_attrs['conds']) && is_array($rel_attrs['conds'])) {
					$conds = array_merge($rel_attrs['conds'], $conds);
				}

				$rm = $rel_attrs['model'];
				$this->relations[$rel] = $rm::get_first($conds)->fetch();
			}

			return $this->relations[$rel];
		}


		/**
		 * Relation getter for belongs_to relations. Saves the value inside this
		 * object and returns the value.
		 *
		 * @param string $rel Relation name
		 * @return null|object
		 */
		protected function get_rel_belongs_to($rel)
		{
			if (empty($this->relations[$rel])) {
				$rel_attrs = $this::get_attr($rel);
				$rel_model = $rel_attrs['model'];
				$rel_model::check_model();

				$idf = any($rel_attrs['foreign_key']) ? $rel_attrs['foreign_key']:$rel_model::get_id_col();
				$idl = any($rel_attrs['is_natural']) ? $rel_model::get_id_col():('id_'.$rel);

				$conds = array($idf => $this->$idl);

				if (any($rel_attrs['conds'])) {
					$conds = array_merge($rel_attrs['conds'], $conds);
				}

				$this->relations[$rel] = $rel_attrs['model']::get_first($conds)->fetch();
			}

			return $this->relations[$rel];
		}


		/**
		 * Get name of table where keys for billinear has_many relation are stored
		 *
		 * @param string $model     Model name
		 * @param array  $rel_attrs Relation definition
		 * @return string
		 */
		public static function get_bilinear_table_name(array $rel_attrs)
		{
			$rel  = $rel_attrs['model'];
			$name = array(static::get_table(), $rel::get_table());

			if (!any($rel_attrs['is_master'])) {
				$name = array_reverse($name);
			}

			return implode('_has_', $name);
		}


		public static function get_schema()
		{
			static::check_model();

			$attrs  = array();
			$schema = array();
			$cname  = get_called_class();
			$list   = static::get_attr_list();

			foreach ($list as $name) {
				if ($name == static::get_id_col()) {
					continue;
				}

				$attr = static::get_attr($name);
				$attr['name'] = $name;

				switch ($attr['type'])
				{
					case 'bool': $attr['type'] = 'boolean'; break;
					case 'varchar': $attr['type'] = 'string'; break;
					case 'json': $attr['type'] = 'object'; break;
					case self::REL_BELONGS_TO: $attr['type'] = 'model'; break;
					case self::REL_HAS_MANY: $attr['type'] = 'collection'; break;
				}

				// Convert attribute model bindings
				if (isset($attr['model'])) {
					$attr['model'] = \System\Loader::get_model_from_class($attr['model']);
				}

				// Convert attribute value options
				if (isset($attr['options'])) {
					if (isset($attr['options'][0]) && $attr['options'][0] == 'callback') {
						$opts = $attr['options'];
						array_shift($opts);
						$opts = call_user_func($opts);
					} else {
						$opts = $attr['options'];
					}

					$attr['options'] = array();

					foreach ($opts as $opt_value=>$opt_name) {
						$attr['options'][] = array(
							'name'  => $opt_name,
							'value' => $opt_value,
						);
					}
				}

				// Word 'default' is keyword in some browsers, so pwf-models use 'def' instead
				if (isset($attr['default'])) {
					$attr['def'] = $attr['default'];
					unset($attr['default']);

					if (in_array($attr['type'], array('image'))) {
						$attr['def'] = \System\Image::from_path($attr['def'])->to_object();
					} else if (in_array($attr['type'], array('file', 'sound'))) {
						$attr['def'] = \System\File::from_path($attr['def'])->to_object();
					}
				}

				if (is_array($attr)) {
					unset($attr[0]);
					$attrs[] = $attr;
				}
			}

			if (any($cname::$conversions)) {
				$schema['conversions'] = $cname::$conversions;
			}

			$schema['attrs'] = $attrs;

			return $schema;
		}


		/** Get generic seoname of instance
		 * @return string
		 */
		public function get_seoname()
		{
			if ($this->has_attr('name')) {
				return $this->id ? ($this->name ? \System\Url::gen_seoname($this->name).'-'.$this->id:$this->id):null;
			} else {
				return $this->id;
			}
		}


		/**
		 * Check all attributes and figure out if object is ready to be saved
		 *
		 * @return bool
		 */
		public function update_check()
		{
			$e = false;

			foreach (static::$required as $attr) {
				if (!$this->data[$attr]) {
					$this->errors[] = 'missing-attr-'.$attr;
					$e = true;
				}
			}

			return !$e;
		}


		/**
		 * Create or update object in database
		 *
		 * @return System\Model\Database
		 */
		public function save()
		{
			$this->run_tasks(\System\Model\Callback::BEFORE_SAVE);
			$model = get_model($this);

			if ($this->update_check()) {

				if (isset($model::$attrs['pass'])) {
					foreach ($model::$attrs['pass'] as $attr) {
						$old_attr = $attr.'_old';

						if (any($this->__get($old_attr)) && $this->$attr != $this->$old_attr) {
							$this->$attr = hash_passwd($this->$attr);
						}
					}
				}

				$nochange = array();
				$data = $this->get_data();

				// Unset attrs that did not change to spare DB
				foreach ($nochange as $attr_name) {
					unset($data[$attr_name]);
				}

				$this::prepare_data($data);

				if (!$this->is_new() && !$this->is_new_object) {
					\System\Database::simple_update($model::get_table(), $model::get_id_col(), $this->id, $data);
				} else {
					$id = \System\Database::simple_insert($model::get_table(), $data);

					if ($id) {
						$this->id = $id;
					} else throw new \System\Error\Database(sprintf('Could not save model "%s".', $model));
				}
			}

			$this->save_relations();
			$this->run_tasks(\System\Model\Callback::AFTER_SAVE);

			return $this;
		}


		/**
		 * Save all relation attribute values
		 *
		 * @return System\Model\Database
		 */
		protected function save_relations()
		{
			foreach ($this::$attrs as $attr=>$def) {
				if ($def['type'] == self::REL_HAS_MANY) {
					$this->save_relation_hasmany($attr);
				}
			}

			return $this;
		}


		/**
		 * Save value for has_many relation
		 *
		 * @param string $attr
		 * @return System\Model\Database
		 */
		protected function save_relation_hasmany($attr)
		{
			if (isset($this->relations[$attr])) {
				$model   = get_model($this);
				$def     = $this::get_attr($attr);
				$value   = $this->validate_relation_hasmany($attr);
				$new     = collect_ids($value);
				$current = collect_ids($this->$attr->fetch());
				$rel_model = $def['model'];

				$ids_save = array_diff($new, $current);
				$ids_delete = array_diff($current, $new);

				if (!empty($def['is_bilinear'])) {
					$table_name = $model::get_bilinear_table_name($def);

					if (any($def['is_master'])) {
						$id_col = $model::get_id_col();
						$foreign_key = $rel_model::get_id_col();
					} else {
						$id_col = $rel_model::get_id_col();
						$foreign_key = $model::get_id_col();
					}

					$ids_save = array_filter($ids_save);
					$ids_delete = array_filter($ids_delete);

					if (any($ids_delete)) {
						$q1 = new \System\Database\Query(array("table" => $table_name));
						$q1
							->where(array($id_col => $this->id), $table_name)
							->where_in($foreign_key, $ids_delete, $table_name)
							->delete();
					}

					if (any($ids_save)) {
						$q2 = new \System\Database\Query(array("table" => $table_name, "cols" => array($id_col, $foreign_key)));

						foreach ($ids_save as $id) {
							$q2->add_insert_data(array($foreign_key => $id, $id_col => $this->id));
						}

						$q2->insert();
					}
				} else {
					$model = get_model($this);
					$foreign = $model::get_rel_bound_to($attr);
					$foreign_key = $def['model']::get_attr($foreign);
					$idc = $rel_model::get_belongs_to_id($foreign);

					if (any($ids_delete)) {
						$model_id = $rel_model::get_id_col();

						if (!empty($foreign_key['is_null'])) {
							$objects = $this->$attr->where_in($model_id, $ids_delete)->fetch();

							foreach ($objects as $obj) {
								$obj->$idc = null;
								$obj->save();
							}
						} else {
							if ($def['model']::ALLOW_RELATION_DELETE) {
								$objects = $this->$attr->where_in($model_id, $ids_delete)->fetch();

								foreach ($objects as $obj) {
									$obj->drop();
								}
							} else throw new \System\Error\Model(
								sprintf("Cannot delete objects of model '%s' by has_many relation change.", $model),
								sprintf(
									"Set 'is_null' attribute of relation '%s' of model '%s' to true or define class constant called 'ALLOW_RELATION_DELETE' to model '%s'",
									$foreign, $def['model'], $def['model']
								)
							);
						}
					}

					foreach ($value as $obj) {
						$obj->$idc = $this->id;
						$obj->save();
					}
				}
			}
		}


		/**
		 * Assign and save relation by IDs
		 *
		 * @param string $rel_name Name of relation
		 * @param array  $ids_new  List of new IDs - others will be deleted
		 * @return $this
		 */
		public function validate_relation_hasmany($attr)
		{
			if (isset($this->relations[$attr])) {
				$model     = get_model($this);
				$def       = $this::get_attr($attr);
				$value     = $this->relations[$attr];
				$corrected = array();

				foreach ($this->relations[$attr] as $val) {
					if (gettype($val) == 'integer') {
						$m = $def['model'];
						$obj = $m::find($val);

						if ($obj) {
							$corrected[] = $m::find($val);
						} else throw new \System\Error\Model(sprintf(
							"Cannot assign object '%s#%s' to instance of '%s'. Object does not exist.",
							$def['model'], $val, $model
						));
					}
				}

				return $corrected;
			} else return array();
		}


		/**
		 * Prepare data to be saved and reJSON
		 *
		 * @param array  $data
		 * @return void
		 */
		protected static function prepare_data(array &$data)
		{
			foreach (static::$attrs as $attr=>$attr_def) {
				if (empty($data[$attr]) && empty($attr_def['is_null']) && any($attr_def['default'])) {
					if ($attr_def['default'] == 'NOW()') {
						$data[$attr] = new \DateTime();
					} else {
						$data[$attr] = $attr_def['default'];
					}
				}

				if ($attr_def['type'] === 'json' && isset($data[$attr])) {
					$data[$attr] = json_encode($data[$attr]);
				}

				if ($attr_def['type'] === 'int_set' && isset($data[$attr])) {
					$data[$attr] = implode(',', $data[$attr]);
				}

				if (isset($data[$attr])) {
					if (is_object($data[$attr])) {
						$empty = false;

						if (method_exists(get_class($data[$attr]), 'is_empty')) {
							$empty = $data[$attr]->is_empty();
						}

						if ($empty) {
							$data[$attr] = null;
						} else {
							if (method_exists(get_class($data[$attr]), 'save')) {
								$data[$attr]->save();
							}

							if (!method_exists($data[$attr], 'to_sql')) {
								if (method_exists(get_class($data[$attr]), 'to_json')) {
									$data[$attr] = $data[$attr]->to_json();
								}
							}
						}
					}
				}
			}
		}


		/**
		 * Delete object from database
		 *
		 * @return bool
		 */
		public function drop()
		{
			return \System\Database\Query::simple_delete($this::get_table(), array($this::get_id_col() => $this->id));
		}


		/**
		 * Does object have an id?
		 *
		 * @return bool
		 */
		public function is_new()
		{
			return !$this->id;
		}


		/**
		 * Reload object from database
		 *
		 * @return System\Model\Database
		 */
		public function reload()
		{
			if ($this->id) {
				$this->update_attrs(
					$this::get_first(array($this::get_id_col() => $this->id))
						->assoc_with_no_model()
						->fetch()
				);
			}

			return $this;
		}


		/**
		 * Get all children of Database model
		 *
		 * @return array List of class names
		 */
		public static function get_all_children()
		{
			$all_classes = get_declared_classes();
			$child_classes = array();

			foreach ($all_classes as $class) {
				if (is_subclass_of('\\'.$class, get_called_class())) {
					$ref = new \ReflectionClass($class);
					if (!$ref->isAbstract()) {
						$child_classes[$class] = $class;
					}
				}
			}

			return $child_classes;
		}


		/**
		 * Get definition of all model relations
		 *
		 * @param string $model
		 * @return array
		 */
		public static function get_model_relations()
		{
			$relations = array();

			foreach (static::$attrs as $attr=>$def) {
				if (static::is_rel($attr)) {
					$relations[$attr] = $def;
				}
			}

			return $relations;
		}


		public static function get_attr_list_sql()
		{
			$model = get_called_class();
			$attrs = $model::get_attr_list();
			$list  = array();

			foreach ($attrs as $attr) {
				$def  = $model::get_attr($attr);
				$type = $model::get_attr_type($attr);

				if (empty($def['is_fake'])) {
					if ($type == 'point') {
						$list[$attr] = 'AsWKT('.$attr.')';
					} else if ($model::is_rel($attr)) {
						if ($type == self::REL_BELONGS_TO) {
							$list[] = $model::get_belongs_to_id($attr);
						}

					} else {
						$list[] = $attr;
					}
				}
			}

			return $list;
		}


		/**
		 * Does attribute exist? Includes model IDs
		 *
		 * @param string $attr Name of attribute
		 * @return bool
		 */
		public static function has_attr($attr)
		{
			$model = get_called_class();
			return $attr == $model::get_id_col() || parent::has_attr($attr);
		}


		/** Override of attr models get_attr, respects 'id' alias
		 * @param string $model
		 * @param string $attr
		 * @return mixed
		 */
		public static function get_attr($attr)
		{
			if ($attr == 'id') {
				$model = get_called_class();
				$attr  = $model::get_id_col();
			}

			return parent::get_attr($attr);
		}


		/** Unified name getter
		 * @param pattern
		 * @return string
		 */
		public function get_name($pattern = null)
		{
			if (is_null($pattern)) {
				return $this->has_attr('name') ? $this->name:$this->id;
			} else {
				return soprintf($pattern, $this);
			}
		}


		/** Get belongs_to relation that is bound to has_many or has_one relation
		 * @param string $model
		 * @param string $rel
		 * @return false|array
		 */
		public static function get_rel_bound_to($rel)
		{
			$model = get_called_class();
			$def = $model::get_attr($rel);
			$match = array();
			$rel_model = $def['model'];

			foreach ($rel_model::$attrs as $attr=>$def_attr) {
				if ($def_attr['type'] == self::REL_BELONGS_TO && $def_attr['model'] == $model) {
					$match[] = $attr;
				}
			}

			if (any($match)) {
				if (count($match) === 1) {
					return $match[0];
				}

				throw new \System\Error\Model(sprintf('Model %s has more belongs_to relations that match', $def['model']));
			}

			throw new \System\Error\Model('Relation target was not found.', $model.'::'.$rel);
		}


		/** Convert model to string
		 * @return string
		 */
		public function __toString()
		{
			return sprintf('[%s#%s]', \System\Loader::get_model_from_class(get_class($this)), $this->is_new() ? 'new':$this->id);
		}


		public function to_object()
		{
			return array_merge($this->to_object_with_id(), $this->get_rels_to_object());
		}


		public function to_object_with_id()
		{
			$model = get_class($this);
			$data = parent::to_object();
			$idc = $this::get_id_col();

			if (isset($data[$idc])) {
				$data['id'] = $data[$idc];
				unset($data[$idc]);
			}

			return $data;
		}


		public function get_rels_to_object()
		{
			$data  = array();
			$attrs = $this::get_attr_list();

			foreach ($attrs as $attr_name) {
				if ($this::is_rel($attr_name)) {
					$def = $this::get_attr($attr_name);

					if ($def['type'] == self::REL_HAS_MANY) {
						$data[$attr_name] = $this->get_rel_has_many_ids($attr_name);
					} else if ($def['type'] == self::REL_BELONGS_TO) {
						$bid = $this::get_belongs_to_id($attr_name);

						if ($this->$bid) {
							$data[$attr_name] = $this->$bid;
						}
					}
				}
			}

			return $data;
		}
	}
}
