<?

/** Database data model handling */
namespace System\Model
{
	/** Simple database model that manages attributes, fetching and saving
	 * data and also database relations: belongs_to, has_one, has_many. Billinear
	 * has_many relation is also supported
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

		/** Table names generated by model name */
		static protected $tables_generated = array();

		/** ID columns generated by model name */
		static protected $id_cols_generated = array();

		/** Allowed model relation types */
		static protected $relation_types = array(self::REL_BELONGS_TO, self::REL_HAS_ONE, self::REL_HAS_MANY);


		static private $models_checked = array();


		/** Detault conditions for get_all */
		static private $quick_conds = array(
			"visible" => true,
			"deleted" => false,
			"used"    => true,
		);


		protected $relations = array();


		/** Get name of associated table
		 * @param string $model Class name
		 * @return string
		 */
		public static function get_table($model)
		{
			if (isset($model::$table)) {
				return $model::$table;
			} elseif (isset(self::$tables_generated[$model])) {
				return self::$tables_generated[$model];
			} else {
				return self::$tables_generated[$model] = implode('_', array_map('strtolower', array_filter(explode('\\', $model))));
			}
		}


		/** Does attribute of a model exist
		 * @param string $model
		 * @param string $attr
		 * @return bool True if exists
		 */
		public static function attr_exists($model, $attr)
		{
			self::check_relations($model);
			return $attr == self::get_id_col($model) || parent::attr_exists($model, $attr);
		}


		/** Does attribute belong to belongs_to relation?
		 * @param string $model
		 * @param string $attr
		 * @return bool
		 */
		public static function check_relations($model)
		{
			if (!isset(self::$models_checked[$model])) {
				self::$models_checked[$model] = true;
				$is_true = false;
				$name = null;

				foreach ($model::$attrs as $attr_name=>$def) {
					if ($def[0] === self::REL_BELONGS_TO) {
						$rel_attr_name = self::get_belongs_to_id($model, $attr_name);
						self::add_attribute($model, $rel_attr_name, self::get_default_belongs_to_def($rel_attr_name, $def));
					}
				}
			}
		}


		public static function get_default_belongs_to_def($name, $def)
		{
			$def = array_merge($def, array(
				"is_unsigned" => true,
				"is_index" => true,
				"is_fake" => true,
				"is_generated" => true,
				"rel" => $name
			));

			$def[0] = 'int';
			return $def;
		}


		private static function add_attribute($model, $attr, array $def)
		{
			$model::$attrs[$attr] = $def;
		}


		public static function get_belongs_to_id($model, $attr)
		{
			if (self::is_rel($model, $attr)) {
				$def = self::get_attr($model, $attr);

				if ($def[0] === self::REL_BELONGS_TO) {
					if (any($def['foreign_key'])) {
						return $def['foreign_key'];
					} else {
						return empty($def['is_natural']) ? ("id_".$attr):self::get_id_col($def['model']);
					}
				}
			}

			throw new \System\Error\Model(sprintf('Attribute "%s" of model "%s" is not belongs_to relation.', $model, $attr));
		}


		/** Get name of ID column
		 * @param string $model Model name
		 * @return string
		 */
		public static function get_id_col($model)
		{
			if (isset($model::$id_col)) {
				return $model::$id_col;
			} elseif (isset(self::$id_cols_generated[$model])) {
				return self::$id_cols_generated[$model];
			} else {
				return self::$id_cols_generated[$model] = 'id_'.self::get_table($model);
			}
		}


		/** Create new object of model and save it
		 * @param string $model Model name
		 * @param array  $attrs Attribute data
		 * @return object
		 */
		public static function create($model, array $attrs)
		{
			$obj = new $model($attrs);
			return $obj->save();
		}


		/** Get all models of a class
		 * @param   string $model Class name desired model
		 * @param   array  $conds Set of conditions
		 * @param   array  $opts  Set of query options
		 * @param   array  $joins Set of query joins
		 * @return array         Set of matched models
		 */
		public static function get_all($model, array $conds = array(), array $opts = array(), array $joins = array())
		{
			if (!$model || !class_exists($model)) throw new \System\Error\Argument(sprintf('Model %s not found', $model));

			if (empty($opts['order-by']) && self::attr_exists($model, 'order')) {
				$opts['order-by'] = "`t0`.`order` ASC";
			}

			$helper = new \System\Database\Query(
				array(
					"table" => self::get_table($model),
					"cols"  => self::get_model_attr_list($model),
					"opts"  => $opts,
					"conds" => $conds,
					"model" => $model,
				)
			);

			if (isset($model::$belongs_to)) {
				if (!is_array(\System\Cache::fetch('basicmodel-merge-attrs-'.$model, $attrs_to_merge))) {

					$attrs_to_merge = array();
					foreach ($model::$belongs_to as $k=>$b) {
						if (isset($b['merge-model']) && $b['merge-model'] && $jmodel = $b['model']) {
							if (!empty($b['cols'])) {
								$attr_def = array();
								foreach ($b['cols'] as $col) {
									$t = self::get_attr_type($jmodel, $col);
									if (!isset($attr_def[$t])) {
										$attr_def[$t] = array();
									}

									$attr_def[$t][] = $col;
								}
							} else {
								$attr_def = $jmodel::$attrs;
							}

							$attrs_to_merge[] = array(self::get_table($jmodel), "USING(".(self::get_id_col($jmodel)).")", 'extension_'.$k, $attr_def);
						}
					}

					\System\Cache::store('basicmodel-merge-attrs-'.$model, $attrs_to_merge);
				}

				if (!isset(self::$merged_attrs[$model])) {
					self::$merged_attrs[$model] = array();

					foreach ($attrs_to_merge as $jattr) {
						self::$merged_attrs[$model] = array_merge_recursive(self::$merged_attrs[$model], $jattr[3]);
					}
				}

				foreach ($attrs_to_merge as $jattr) {
					$helper->join($jattr[0], $jattr[1], $jattr[2]);
					$helper->add_cols($jattr[3], $jattr[2]);
				}
			}

			if (any($joins)) {
				foreach ($joins as $join) {
					def($join[3], '');
					$join[0] ?
						$helper->left_join($join[1], $join[2], $join[3]):
						$helper->join($join[1], $join[2], $join[3]);
				}
			}

			return $helper;
		}


		/** Find model by ID or set of IDs
		 * @param string    $model      Class name of desired model
		 * @param int|array $ids        ID or list of IDs to look for
		 * @param bool      $force_list Returns array even if single result
		 * @return self|array
		 */
		public static function find($model, $ids = NULL, $force_list = false)
		{
			if (is_array($ids) || ($ex = strpos($ids, ','))) {

				any($ex) && $ids = explode(',', $ids);
				$conds = array(self::get_id_col($model). " IN ('" .implode('\',\'', $ids)."')");
				return self::get_all($model, $conds)->fetch();

			} else {

				$col = self::get_id_col($model);
				if (!is_numeric($ids)) {
					if (self::attr_exists($model, 'seoname')) {
						$col = 'seoname';
					} else {
						$ids = \System\Url::get_seoid($ids);
					}
				}

				$conds = array($col => $ids);
				$result = self::get_first($model, $conds)->fetch();

				return $force_list ? array($result):$result;
			}
		}


		/** Get first instance of matched query
		 * @param mixed $model  Class or instance of desired model
		 * @param array $conds  Set of conditions
		 * @param array $opts   Set of query options
		 * @return BasicModel
		 */
		public static function get_first($model, array $conds = array(), array $opts = array())
		{
			$opts['limit'] = 1;
			$opts['first'] = true;

			return self::get_all($model, $conds, $opts);
		}


		/** Count all
		 * @param mixed $model  Class or instance of desired model
		 * @param array $conds  Set of conditions
		 * @param array $opts   Set of query options
		 * @return int
		 */
		public static function count_all($model, array $conds = array(), array $opts = array())
		{
			$helper = self::get_all($model, $conds, $opts);
			return $helper->count();
		}


		/** Is model attribute of type relation?
		 * @param mixed  $model Class or instance of desired model
		 * @param string $attr  Name of atribute
		 * @return bool
		 */
		public static function is_rel($model, $attr)
		{
			if (self::attr_exists($model, $attr)) {
				$def = self::get_attr($model, $attr);
				return in_array($def[0], self::$relation_types);
			}

			return false;
		}


		/** Public attribute handler, setter override for database model. Handles extra relations.
		 * @param string $name  Attribute handler
		 * @param mixed  $value Value to be set
		 * @return BasicModel
		 * @see \System\Model\Attr::__set()
		 */
		public function __set($attr, $value)
		{
			$model = get_model($this);

			if (self::is_rel($model, $attr)) {
				$type = self::get_attr_type($model, $attr);

				if ($type == self::REL_HAS_MANY) {
					$this->set_rel_has_many($attr, $value);
				} else {
					$this->set_rel_single_value($attr, $value);
				}

				return $this;
			}

			if ($attr == 'id' || $attr == self::get_id_col(get_model($this))) {
				$this->data[self::get_id_col(get_model($this))] = intval($value);
			}

			return parent::__set($attr, $value);
		}


		public function set_rel_has_many($attr, $value)
		{
			$invalid = false;

			if (empty($value)) {
				$value = array();
			}

			if (is_array($value)) {
				$corrected = array();
				$model = get_model($this);
				$def = self::get_attr($model, $attr);

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

				$this->relations[$attr] = $value;
			} else throw new \System\Error\Model(sprintf(
				"Value given to has_many relations must be array. Type of '%s' was supplied to attribute '%s' of model '%s'.",
				gettype($value), $attr, get_model($this)
			));
		}


		/** Sets value for relations that lead to single object. Called internally by __set on belongs_to and has_one relations
		 * @param string                 $name  Attribute name
		 * @param \System\Model\Database $value Value object
		 * @return $this
		 */
		public function set_rel_single_value($name, $value)
		{
			$model = get_model($this);
			$type = self::get_attr_type($model, $name);
			$def = self::get_attr($model, $name);
			$value = empty($value) ? null:$value;
			$is_null = !empty($def['is_null']) && is_null($value);

			if (is_numeric($value)) {
				$val = find($def['model'], $value);

				if ($val) {
					$value = $val;
				} else throw new \System\Error\Model(
						"Object not found.",
						sprintf("Integer value '%s' was given to attribute '%s' of model '%s' but related object of model '%s' was not found.", $value, $name, $model, $def['model'])
					);
			}

			if ($type == self::REL_BELONGS_TO || $type == self::REL_HAS_ONE) {
				if (is_object($value) || $is_null) {
					if (($value instanceof $def['model']) || $is_null) {
						$this->relations[$name] = $value;

						if ($type == self::REL_BELONGS_TO) {
							$idc = self::get_belongs_to_id($model, $name);
							$this->$idc = $is_null ? null:$value->id;
						}

					} else throw new \System\Error\Argument(sprintf(
						"Value for attr '%s' of model '%s' must be instance of '%s' by definition. Instance of '%s' was given.",
						$name, $model, $def['model'], get_model($value)
					));
				} else throw new \System\Error\Argument(sprintf(
					"Value for attr '%s' of model '%s' which is '%s' relation must be object that inherits System\Model\Database. '%s' was given.",
					$name, $model, $type, gettype($value)
				));
			}

			return $this;
		}


		/** Attribute getter
		 * @param string $attr
		 * @return mixed
		 */
		public function __get($attr)
		{
			$model = get_model($this);
			if (self::is_rel($model, $attr)) {
				return $this->get_rel($attr);
			} else {
				if ($attr == 'id' || $attr == self::get_id_col(get_model($this))) {
					return def($this->data[self::get_id_col(get_model($this))], 0);
				}

				return parent::__get($attr);
			}
		}


		/** Relation getter for all supported relation types.
		 * @param   string $rel   Name of the relation
		 * @return null|\System\Model\Database|\System\Database\Query Depends on relation type
		 */
		protected function get_rel($rel)
		{
			$model = get_model($this);

			if (self::is_rel($model, $rel)) {
				$type = self::get_attr_type($model, $rel);

				if ($type == self::REL_HAS_MANY) {
					return $this->get_rel_has_many($rel);
				} elseif ($type == self::REL_HAS_ONE) {
					return $this->get_rel_has_one($rel);
				} elseif ($type == self::REL_BELONGS_TO) {
					return $this->get_rel_belongs_to($rel);
				} else throw new \System\Error\Argument(sprintf(
					"Attribute '%s' of model '%s' is not a relation of any known type.",
					$model, $rel
				));
			} throw new \System\Error\Argument(sprintf(
				"Attribute '%s' of model '%s' is not a relation of any kind.",
				$model, $rel
			));
		}


		/** Relation getter for has_many relations. Returns query object
		 * @param string $rel Relation name
		 * @return System\Database\Query
		 */
		protected function get_rel_has_many($rel)
		{
			$model = get_model($this);
			$rel_attrs = self::get_attr($model, $rel);
			$join_alias = 't0';
			$helper = get_all($rel_attrs['model'], array(), array());

			if (any($rel_attrs['is_bilinear'])) {
				$join_alias = 't_'.$rel;
				$table_name = self::get_bilinear_table_name($model, $rel_attrs);
				$helper->join($table_name, "USING(".self::get_id_col($rel_attrs['model']).")", $join_alias);
				$idc = any($rel_attrs['foreign_name']) ? $rel_attrs['foreign_name']:self::get_id_col($model);
			} else {
				if ($foreign = self::get_rel_bound_to($model, $rel)) {
					$idc = self::get_belongs_to_id($rel_attrs['model'], $foreign);
				} else throw new \System\Error\Model(
					"Could not find model relation.",
					sprintf("There is no relation between '%s::%s' (has_many) and model %s (belongs_to)", $model, $rel, $rel_attrs['model'])
				);
			}

			self::attr_exists($rel_attrs['model'], 'order') && $helper->add_opts(array("order-by" => "`t0`.".'`order` ASC'));


			$helper->where(array($idc => $this->id), $join_alias);
			$helper->assoc_with($rel_attrs['model']);

			$this->id ? $helper->cancel_ignore():$helper->ignore_query(array());
			return $helper;
		}


		protected function get_rel_has_many_ids($rel)
		{
			$def = self::get_attr(get_class($this), $rel);
			$idc = self::get_id_col($def['model']);
			$data = $this->$rel->reset_cols()->add_cols($idc, 't0')->assoc_with(null)->fetch();
			$ids = array();

			foreach ($data as $row) {
				$ids[] = intval($row[$idc]);
			}

			return $ids;
		}


		/** Relation getter for has_one relations. Saves the value inside this object and returns the value.
		 * @param string $rel Relation name
		 * @return null|object
		 */
		protected function get_rel_has_one($rel)
		{
			if (empty($this->relations[$rel])) {
				$model = get_model($this);
				$rel_attrs = self::get_attr($model, $rel);

				if (any($rel_attrs['foreign_key'])) {
					$conds = array($rel_attrs['foreign_key'] => $this->id);
				} else {
					$idc = any($rel_attrs['foreign_name']) ? 'id_'.$rel_attrs['foreign_name']:self::get_id_col($model);
					$conds = array($idc => $this->id);
				}

				if ($rel_attrs['conds']) {
					$conds = array_merge($rel_attrs['conds'], $conds);
				}

				$this->relations[$rel] = get_first($rel_attrs['model'], $conds)->fetch();
			}

			return $this->relations[$rel];
		}


		/** Relation getter for belongs_to relations. Saves the value inside this object and returns the value.
		 * @param string $rel Relation name
		 * @return null|object
		 */
		protected function get_rel_belongs_to($rel)
		{
			if (empty($this->relations[$rel])) {
				$model = get_model($this);
				$rel_attrs = self::get_attr($model, $rel);
				$idf = any($rel_attrs['foreign_key']) ? $rel_attrs['foreign_key']:self::get_id_col($rel_attrs['model']);
				$idl = any($rel_attrs['is_natural']) ? self::get_id_col($rel_attrs['model']):('id_'.$rel);

				$conds = array($idf => $this->$idl);

				if (any($rel_attrs['conds'])) {
					$conds = array_merge($rel_attrs['conds'], $conds);
				}

				$this->relations[$rel] = get_first($rel_attrs['model'], $conds)->fetch();
			}

			return $this->relations[$rel];
		}


		/** Get name of table where keys for billinear has_many relation are stored
		 * @param string $model     Model name
		 * @param array  $rel_attrs Relation definition
		 * @return string
		 */
		public static function get_bilinear_table_name($model, array $rel_attrs)
		{
			$name = array();

			if (any($rel_attrs['is_master'])) {
				$name['master'] = \System\Model\Database::get_table($model);
				$name['slave']  = \System\Model\Database::get_table($rel_attrs['model']);
			} else {
				$name['master'] = \System\Model\Database::get_table($rel_attrs['model']);
				$name['slave']  = \System\Model\Database::get_table($model);
			}

			return implode('_has_', $name);
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


		/** Check all attributes and figure out if object is ready to be saved
		 * @return bool True if all ok
		 */
		public function update_check()
		{
			$model = get_model($this);
			$e = false;

			if (isset($model::$required)) {
				foreach ($model::$required as $attr) {
					if (!$this->data[$attr]) {
						//~ $this->errors[] = 'missing-attr-'.$attr;
						$e = true;
					}
				}
			}
			return !$e;
		}


		/** Create or update object in database
		 * @return BasicModel
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

				self::prepare_data($model, $data);

				if ($this->id && !$this->is_new_object) {
					\System\Database::simple_update(self::get_table($model), self::get_id_col($model), $this->id, $data);
				} else {
					$id = \System\Database::simple_insert(self::get_table($model), $data);

					if ($id) {
						$this->id = $id;
					} else throw new \System\Error\Database(sprintf('Could not save model "%s".', $model));
				}
			}

			$this->save_relations();

			$this->run_tasks(\System\Model\Callback::AFTER_SAVE);
			return $this;
		}


		public function save_relations()
		{
			$model = get_model($this);

			foreach ($model::$attrs as $attr=>$def) {
				if ($def[0] == self::REL_HAS_MANY) {
					$this->save_relation_hasmany($attr);
				}

				if ($def[0] == self::REL_HAS_ONE) {
				}
			}
		}


		protected function save_relation_hasmany($attr)
		{
			if (isset($this->relations[$attr])) {
				$model   = get_model($this);
				$def     = self::get_attr($model, $attr);
				$value   = $this->validate_relation_hasmany($attr);
				$new     = collect_ids($value);
				$current = collect_ids($this->$attr->fetch());

				$ids_save = array_diff($new, $current);
				$ids_delete = array_diff($current, $new);

				if (!empty($def['is_bilinear'])) {
					$table_name = self::get_bilinear_table_name($model, $def);

					if (any($def['is_master'])) {
						$id_col = self::get_id_col($model);
						$foreign_key = self::get_id_col($def['model']);
					} else {
						$id_col = self::get_id_col($def['model']);
						$foreign_key = self::get_id_col($model);
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
					$foreign = self::get_rel_bound_to($model, $attr);
					$foreign_key = self::get_attr($def['model'], $foreign);
					$idc = self::get_belongs_to_id($def['model'], $foreign);

					if (any($ids_delete)) {
						$model_id = self::get_id_col($def['model']);

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


		/** Assign and save relation by IDs
		 * @param string $rel_name Name of relation
		 * @param array  $ids_new  List of new IDs - others will be deleted
		 * @return $this
		 */
		public function validate_relation_hasmany($attr)
		{
			if (isset($this->relations[$attr])) {
				$model     = get_model($this);
				$def       = self::get_attr($model, $attr);
				$value     = $this->relations[$attr];
				$corrected = array();

				foreach ($this->relations[$attr] as $val) {
					if (gettype($val) == 'integer') {
						$obj = find($def['model'], $val);

						if ($obj) {
							$corrected[] = find($def['model'], $val);
						} else throw new \System\Error\Model(sprintf(
							"Cannot assign object '%s#%s' to instance of '%s'. Object does not exist.",
							$def['model'], $val, $model
						));
					}
				}

				return $corrected;
			} else return array();
		}


		/** Prepare data to be saved (ReJSON)
		 * @param string $model
		 * @param array  $data
		 * @return void
		 */
		protected static function prepare_data($model, array &$data)
		{
			foreach ($model::$attrs as $attr=>$attr_def) {
				if (empty($data[$attr]) && empty($attr_def['is_null']) && any($attr_def['default'])) {
					if ($attr_def['default'] == 'NOW()') {
						$data[$attr] = new \DateTime();
					} else {
						$data[$attr] = $attr_def['default'];
					}
				}

				if ($attr_def[0] === 'json' && isset($data[$attr])) {
					$data[$attr] = json_encode($data[$attr]);
				}

				if ($attr_def[0] === 'int_set' && isset($data[$attr])) {
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


		/** Delete object from database
		 * @return bool
		 */
		public function drop()
		{
			$model = get_model($this);
			return \System\Database\Query::simple_delete(self::get_table($model), array(self::get_id_col($model) => $this->id));
		}


		/** Get generic conditions for BasicModel
		 * @param  mixed $model Instance or class name of model
		 * @return array Set of conditions
		 */
		public static function get_quick_conds($model)
		{
			if (is_object($model)) {
				$model = get_model($model);
			}

			$conds = array();
			foreach (self::$quick_conds as $attr=>$val) {
				self::attr_exists($model, $attr) && $conds[$attr] = $val;
			}
			return $conds;
		}


		/** Does object have an id?
		 * @return bool
		 */
		public function is_new()
		{
			return !!$this->id;
		}


		/** Reload object from database
		 * @return BasicModel
		 */
		public function reload()
		{
			$model = get_model($this);
			if ($this->id) {
				$this->update_attrs(get_first($model, array(self::get_id_col($model) => $this->id))->assoc_with_no_model()->fetch());
			}

			return $this;
		}


		/** Get all children of Database model
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


		/** Get definition of all model relations
		 * @param string $model
		 * @return array
		 */
		public static function get_model_relations($model)
		{
			$relations = array();

			foreach ($model::$attrs as $attr=>$def) {
				if (self::is_rel($model, $attr)) {
					$relations[$attr] = $def;
				}
			}

			return $relations;
		}


		/** Get list of model attributes
		 * @param string $model      Name of model class
		 * @param bool   $sql_format Format names to sql
		 * @return array
		 */
		public static function get_model_attr_list($model, $sql_format = true, $with_rels = false)
		{
			$attrs = array(self::get_id_col($model));

			foreach ($model::$attrs as $attr=>$def) {
				if (empty($def['is_fake'])) {
					if ($sql_format && $def[0] === 'point') {
						$attrs[$attr] = 'AsWKT('.$attr.')';
					} else {
						if ($attr != self::get_id_col($model)) {
							if (self::is_rel($model, $attr) && !$with_rels) {
								$type = self::get_attr_type($model, $attr);

								if ($type === self::REL_BELONGS_TO) {
									$attrs[] = self::get_belongs_to_id($model, $attr);
								}

							} else $attrs[] = $attr;
						}
					}
				}
			}

			!in_array('created_at', $attrs) && $attrs[] = 'created_at';
			!in_array('updated_at', $attrs) && $attrs[] = 'updated_at';

			return $attrs;
		}


		/** Instance version of model_attr_exist
		 * @param string $attr Name of attribute
		 * @return bool
		 */
		public function has_attr($attr)
		{
			return self::attr_exists(get_model($this), $attr);
		}


		/** Override of constructor, adds id column, created_at and updated_at into attrs
		 * @param array $update
		 * @return new object
		 */
		public function __construct(array $update = array())
		{
			$model = get_model($this);
			parent::check_properties($model);

			if (!array_key_exists($idc = self::get_id_col($model), $model::$attrs)) {
				$model::$attrs[$idc] = array("int", "is_unsigned" => true, "is_index" => true);
			}

			if (!$this->has_attr('created_at')) {
				$model::$attrs['created_at'] = array('datetime', "default" => 'NOW()');
			}

			if (!$this->has_attr('updated_at')) {
				$model::$attrs['updated_at'] = array('datetime');
			}

			return parent::__construct($update);
		}


		/** Override of attr models get_attr, respects 'id' alias
		 * @param string $model
		 * @param string $attr
		 * @return mixed
		 */
		public static function get_attr($model, $attr)
		{
			if ($attr === 'id') {
				$attr = self::get_id_col($model);
			}

			return parent::get_attr($model, $attr);
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
		public static function get_rel_bound_to($model, $rel)
		{
			$def = self::get_attr($model, $rel);
			$match = array();

			foreach ($def['model']::$attrs as $attr=>$def_attr) {
				if ($def_attr[0] == self::REL_BELONGS_TO) {
					if ($def_attr['model'] == $model) {
						$match[] = $attr;
					}
				}
			}

			if (any($match)) {
				if (count($match) === 1) {
					return $match[0];
				} else throw new \System\Error\Model(sprintf('Model %s has more belongs_to relations that match', $def['model']));
			}

			return false;
		}


		/** Convert attr model to html
		 * @param \System\Template\Renderer $ren
		 * @return string
		 */
		public function to_html(\System\Template\Renderer $ren)
		{
			return sprintf('%s %s (#%s)', $ren->locales()->trans_class_name(get_class($this)), $this->get_name(), $this->id);
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
			$idc = self::get_id_col(get_class($this));

			if (isset($data[$idc])) {
				$data['id'] = $data[$idc];
				unset($data[$idc]);
			}

			return $data;
		}


		public function get_rels_to_object()
		{
			$model = get_class($this);
			$data  = array();
			$attrs = \System\Model\Database::get_model_attr_list($model, false, true);

			foreach ($attrs as $attr_name) {
				if (self::is_rel($model, $attr_name)) {
					$def = self::get_attr($model, $attr_name);

					if ($def[0] == self::REL_HAS_MANY) {
						$data[$attr_name] = $this->get_rel_has_many_ids($attr_name);
					} else if ($def[0] == self::REL_BELONGS_TO) {
						$bid = self::get_belongs_to_id($model, $attr_name);

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
