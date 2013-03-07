<?

namespace System\Model
{
	abstract class Database extends Callback
	{
		// Replace chars
		private static $bad_chars  = array(' ', '_', '--', '.');
		private static $good_chars = array('-', '-', '-', '', '');

		private static $strictly_bad_chars = array('-');
		private static $strictly_good_chars = array('_');

		// Basic
		static protected $table;
		static protected $id_col;
		static protected $tables_generated = array();
		static protected $id_cols_generated = array();

		// Relations
		static protected $relation_types = array('belongs_to', 'has_one', 'has_many');
		static protected $belongs_to;
		static protected $has_one;
		static protected $has_many;

		// Detault conditions for get_all
		static private $quick_conds = array(
			"visible" => true,
			"deleted" => false,
			"used"    => true,
		);


		/* Get name of associated table
		 * @returns string
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
		 * @returns bool True if exists
		 */
		public static function attr_exists($model, $attr)
		{
			return $attr == self::get_id_col($model) || parent::attr_exists($model, $attr) || self::is_model_belongs_to_id($model, $attr);
		}


		public static function is_model_belongs_to_id($model, $attr)
		{
			$is_true = false;
			$name = null;

			if (any($model::$belongs_to)) {
				foreach ($model::$belongs_to as $rel_name=>$rel) {
					$rel_attr_name = self::get_attr_name_from_belongs_to_rel($rel_name, $rel);
					if ($attr == $rel_attr_name) {
						$is_true = true;
						$name = $rel_attr_name;
					}

					if ($is_true) break;
				}
			}

			if ($is_true) {
				if (!isset($model::$attrs[$name])) {
					$model::$attrs[$name] = array("int", "is_unsigned" => true, "is_index" => true);
				}
			}

			return $is_true;
		}


		public static function get_attr_name_from_belongs_to_rel($rel_name, $rel)
		{
			if (any($rel['foreign_key'])) {
				return $rel['foreign_key'] === $attr;
			} else {
				return any($rel['is_natural']) ? self::get_id_col($rel['model']):"id_".$rel_name;
			}

			return false;
		}


		/** Get name of ID column
		 * @returns string
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


		public static function create($model, array $attrs)
		{
			$obj = new $model($attrs);
			return $obj->save();
		}


		/* Get all models of a class
		 * @param   string $model Class name desired model
		 * @param   array  $conds Set of conditions
		 * @param   array  $opts  Set of query options
		 * @param   array  $joins Set of query joins
		 * @returns array         Set of matched models
		 */
		public static function get_all($model, array $conds = array(), array $opts = array(), array $joins = array())
		{
			if (!$model || !class_exists($model)) throw new \System\Error\Argument(sprintf('Model %s not found', $model));

			if (empty($opts['order-by']) && self::attr_exists($model, 'order')) {
				$opts['order-by'] = "`t0`.`order` ASC";
			}

			$helper = new \System\Query(
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
		 * @returns self|array
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
						$ids = intval(substr($ids, strlen($ids) - strpos(strrev($ids), '-')));
					}
				}

				$conds = array($col => $ids);
				$result = self::get_first($model, $conds)->fetch();

				return $force_list ? array($result):$result;
			}
		}


		/* Get first instance of matched query
		 * @param mixed $model  Class or instance of desired model
		 * @param array $conds  Set of conditions
		 * @param array $opts   Set of query options
		 * @returns BasicModel
		 */
		public static function get_first($model, array $conds = array(), array $opts = array())
		{
			$opts['limit'] = 1;
			$opts['first'] = true;

			return self::get_all($model, $conds, $opts);
		}


		/* Count all
		 * @param mixed $model  Class or instance of desired model
		 * @param array $conds  Set of conditions
		 * @param array $opts   Set of query options
		 * @returns int
		 */
		public static function count_all($model, array $conds = array(), array $opts = array())
		{
			$helper = self::get_all($model, $conds, $opts);
			return $helper->count();
		}


		/** Is model attribute of type relation?
		 * @param mixed  $model Class or instance of desired model
		 * @param string $attr  Name of atribute
		 * @returns bool
		 */
		public static function attr_is_rel($model, $attr)
		{
			return
					 (isset($model::$has_many)   && is_array($model::$has_many)  && array_key_exists($attr, $model::$has_many))
				|| (isset($model::$has_one)    && is_array($model::$has_one)    && array_key_exists($attr, $model::$has_one))
				|| (isset($model::$belongs_to) && is_array($model::$belongs_to) && array_key_exists($attr, $model::$belongs_to));
		}


		/** Get type of model relation
		 * @param mixed  $model Class or instance of desired model
		 * @param string $attr  Name of relation
		 * @returns string, false on failure
		 */
		public static function get_rel_type($model, $attr)
		{
			$type = false;

			if (self::attr_is_rel($model, $attr)) {
				if     (is_array($model::$has_many)   && array_key_exists($attr, $model::$has_many))   $type = 'has-many';
				elseif (is_array($model::$has_one)    && array_key_exists($attr, $model::$has_one))    $type = 'has-one';
				elseif (is_array($model::$belongs_to) && array_key_exists($attr, $model::$belongs_to)) $type = 'belongs-to';
			}

			return $type;
		}


		/** Attribute handler, setter
		 * @param string $name  Attribute handler
		 * @param mixed  $value Value to be set
		 * @returns BasicModel
		 */
		public function __set($name, $value)
		{
			$model = get_class($this);
			if (self::attr_is_rel($model, $name)) {
				$type = self::get_rel_type($model, $name);

				if ($type != 'has-many') {
					$this->$name = $value;
					return $this;
				}
			}

			if ($name == 'id' || $name == self::get_id_col(get_class($this))) {
				$this->data[self::get_id_col(get_class($this))] = intval($value);
			}

			return parent::__set($name, $value);
		}


		/** Attribute getter
		 * @param string $name
		 * @returns mixed
		 */
		public function __get($attr)
		{
			$model = get_class($this);
			if (self::attr_is_rel($model, $attr)) {
				return $this->get_rel($model, $attr);
			} else {

				if ($attr == 'author' && isset($model::$attrs['int']) && in_array('id_author', $model::$attrs['int'])) {
					$model::$belongs_to['author'] = array("model" => '\System\User', "local-key" => 'id_author');
					return self::__get($attr);
				}

				if ($attr == 'id' || $attr == self::get_id_col(get_class($this))) {
					return def($this->data[self::get_id_col(get_class($this))], 0);
				}

				return parent::__get($attr);
			}

		}


		/** Get relation data
		 * @param   mixed  $model Instance or class name of model
		 * @param   string $rel   Name of the relation
		 * @returns mixed Relation data, usually array of BasicModels
		 */
		public function get_rel($model, $rel)
		{
			$type = self::get_rel_type($model, $rel);

			if (empty($this->opts[$rel.'-fetched'])) {
				if ($type == 'has-many') {

					$join_alias = 't0';
					$rel_attrs = $model::$has_many[$rel];
					$helper = get_all($rel_attrs['model'], array(), array());

					if (any($rel_attrs['is_bilinear'])) {
						$join_alias = 't_'.$rel;
						$table_name = self::get_bilinear_table_name($model, $rel_attrs);
						$helper->join($table_name, "USING(".self::get_id_col($rel_attrs['model']).")", $join_alias);
					}

					self::attr_exists($rel_attrs['model'], 'order') && $helper->add_opts(array("order-by" => "`t0`.".'`order` ASC'));

					$idc = any($rel_attrs['foreign_name']) ? $rel_attrs['foreign_name']:self::get_id_col($model);

					$helper->where(array($idc => $this->id), $join_alias);
					$helper->assoc_with($rel_attrs['model']);

					$this->id ? $helper->cancel_ignore():$helper->ignore_query(array());
					return $helper;

				} elseif ($type == 'has-one') {

					$rel_attrs = $model::$has_one[$rel];
					if (any($rel_attrs['foreign_key'])) {
						$conds = array($rel_attrs['foreign_key'] => $this->id);
					} else {
						$idc = any($rel_attrs['foreign_name']) ? 'id_'.$rel_attrs['foreign_name']:self::get_id_col($model);
						$conds = array($idc => $this->id);
					}

					if ($rel_attrs['conds']) {
						$conds = array_merge($rel_attrs['conds'], $conds);
					}

					$this->$rel = get_first($rel_attrs['model'], $conds)->fetch();
					$this->opts[$rel.'-fetched'] = true;

				} elseif ($type == 'belongs-to') {

					$rel_attrs = $model::$belongs_to[$rel];
					$idf = any($rel_attrs['foreign_key']) ? $rel_attrs['foreign_key']:self::get_id_col($rel_attrs['model']);
					$idl = any($rel_attrs['is_natural']) ? self::get_id_col($rel_attrs['model']):('id_'.$rel);

					$conds = array($idf => $this->$idl);

					if (any($rel_attrs['conds'])) {
						$conds = array_merge($rel_attrs['conds'], $conds);
					}

					$this->$rel = get_first($rel_attrs['model'], $conds)->fetch();
					$this->opts[$rel.'-fetched'] = true;

				}
			}

			return $this->$rel;
		}


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
		 * @returns string
		 */
		public function get_seoname()
		{
			return $this->id ? self::gen_seoname($this->name).'-'.$this->id:null;
		}


		/** Check all attributes and figure out if object is ready to be saved
		 * @returns bool True if all ok
		 */
		public function update_check()
		{
			$model = get_class($this);
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
		 * @returns BasicModel
		 */
		public function save()
		{
			$this->run_tasks(\System\Model\Callback::BEFORE_SAVE);
			$model = get_class($this);
			if ($this->update_check()) {

				if (isset($model::$attrs['pass'])) {
					foreach ($model::$attrs['pass'] as $attr) {
						$old_attr = $attr.'_old';

						if (any($this->__get($old_attr)) && $this->$attr != $this->$old_attr) {
							$this->$attr = hash_passwd($this->$attr);
						}
					}
				}

				if ($this->has_attr($at = 'id_user_author') || $this->has_attr($at = 'id_author')) {
					!$this->$at && ($this->$at = intval(user()->id));
				}

				$nochange = array();

				foreach (self::$obj_attrs as $attr) {
					if (isset($model::$attrs[$attr])) {

						// Store or delete the image when making changes
						foreach ($model::$attrs['image'] as $name) {
							if (is_object($this->$name)) {
								if ($this->$name->allow_save()) {
									$this->$name->save();
								} elseif ($this->$name->is_to_be_deleted()) {
									$this->data[$name] = null;
								} else $nochange[] = $name;
							} else $nochange[] = $name;
						}

					}
				}

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
						return $this->update_attrs(array(self::get_id_col($model) => $id));
					} else throw new \System\Error\Database('Could not save model.');
				}
			}

			$this->run_tasks(\System\Model\Callback::AFTER_SAVE);
			return $this;
		}


		/** Prepare data to be saved (ReJSON)
		 * @returns void
		 */
		protected static function prepare_data($model, array &$data)
		{
			foreach ($model::$attrs as $attr=>$attr_def) {
				if (empty($data[$attr]) && empty($attr_def['is_null']) && any($attr_def['default'])) {
					$data[$attr] = $attr_def['default'];
				}

				if ($attr_def[0] === 'json' && isset($data[$attr])) {
					$data[$attr] = json_encode($data[$attr]);
				}

				if ($attr_def[0] === 'int_set' && isset($data[$attr])) {
					$data[$attr] = implode(',', $data[$attr]);
				}
			}
		}


		/** Delete object from database
		 * @returns bool
		 */
		public function drop()
		{
			$model = get_class($this);
			return \System\Query::simple_delete(self::get_table($model), array(self::get_id_col($model) => $this->id));
		}


		/** Get generic conditions for BasicModel
		 * @param  mixed $model Instance or class name of model
		 * @return array Set of conditions
		 */
		public static function get_quick_conds($model)
		{
			if (is_object($model)) {
				$model = get_class($model);
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
		 * @returns BasicModel
		 */
		public function reload()
		{
			$model = get_class($this);
			if ($this->id) {
				$this->update_attrs(get_first($model, array(self::get_id_col($model) => $this->id))->assoc_with_no_model()->fetch());
			}

			return $this;
		}


		/** Replace bad characters and generate seoname from string
		 * @param string $str
		 * @param bool   $strict Replace even dashes
		 * @returns string
		 */
		public static function gen_seoname($str, $strict = false)
		{
			$str = strtolower(strip_tags(iconv('UTF-8', 'US-ASCII//TRANSLIT', str_replace(self::$bad_chars, self::$good_chars, $str))));
			return $strict ? str_replace(self::$strictly_bad_chars, self::$strictly_good_chars, $str):$str;
		}


		/** Returns model ID from URL
		 * @param string $str
		 * @returns in ID
		 */
		public static function get_seoid($str)
		{
			return (int) end(explode('-', $str));
		}


		public static function get_all_children()
		{
			$all_classes = get_declared_classes();
			$child_classes = array();
			foreach ($all_classes as $class) {
				if (is_subclass_of('\\'.$class, get_called_class())) {
					$ref = new \ReflectionClass($class);
					if (!$ref->isAbstract()) {
						$child_classes[] = $class;
					}
				}
			}

			return $child_classes;
		}


		public static function get_model_relations($model)
		{
			$relations = array();

			foreach (self::$relation_types as $type) {
				if (isset($model::$$type)) {
					foreach ($model::$$type as $rel_name => $rel_def) {
						$rel_def['type'] = $type;
						$relations[$rel_name] = $rel_def;
					}
				}
			}

			return $relations;
		}


		public static function get_rel_def($model, $rel)
		{
			if (isset($model::$has_many) && isset($model::$has_many[$rel])) {
				return $model::$has_many[$rel];
			} elseif (isset($model::$has_one) && isset($model::$has_one[$rel])) {
				return $model::$has_one[$rel];
			} elseif (isset($model::$belongs_to) && isset($model::$belongs_to[$rel])) {
				return $model::$belongs_to[$rel];
			} else throw new \System\Error\Database("Relation '".$rel."' does not exist.");
		}


		/* Get list of model attributes
		 * @param string $model Name of model class
		 * @returns array
		 */
		public static function get_model_attr_list($model)
		{
			$attrs = array(self::get_id_col($model));

			foreach ($model::$attrs as $attr=>$def) {
				if (empty($def['is_fake'])) {
					if ($def[0] === 'point') {
						$attrs[$attr] = 'AsWKT('.$attr.')';
					} else {
						if ($attr != self::get_id_col($model)) {
							$attrs[] = $attr;
						}
					}
				}
			}

			if (any($model::$belongs_to)) {
				foreach ($model::$belongs_to as $rel_name=>$rel) {
					$name = self::get_attr_name_from_belongs_to_rel($rel_name, $rel);

					if (empty($model::$attrs[$name])) {
						$model::$attrs[$name] = array("int", "is_unsigned" => true, "is_index" => true);
						$attrs[] = $name;
					}
				}
			}

			!in_array('created_at', $attrs) && $attrs[] = 'created_at';
			!in_array('updated_at', $attrs) && $attrs[] = 'updated_at';

			return $attrs;
		}


		private function relation_save($model, $model_id, $rel_name, array $ids_save, array $ids_delete)
		{
			if (isset($model::$has_many[$rel_name])) {
				$def = $model::$has_many[$rel_name];

				if (isset($def['is_bilinear'])) {
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
						$q1 = new \System\Query(array("table" => $table_name));
						$q1
							->where(array($id_col => $model_id), $table_name)
							->where_in($foreign_key, $ids_delete, $table_name)
							->delete();
					}

					if (any($ids_save)) {
						$q2 = new \System\Query(array("table" => $table_name, "cols" => array($id_col, $foreign_key)));

						foreach ($ids_save as $id) {
							$q2->add_insert_data(array($foreign_key => $id, $id_col => $model_id));
						}

						$q2->insert();
					}
				} else throw new \System\Error\Database("Relation must be bilinear!");
			}
		}


		public function assign_rel($rel_name, array $ids_new)
		{
			$model = get_class($this);

			if (isset($model::$has_many[$rel_name])) {
				$def = $model::$has_many[$rel_name];
				$items_current = collect_ids($this->$rel_name->fetch());
				self::relation_save($model, $this->id, $rel_name, array_diff($ids_new, $items_current), array_diff($items_current, $ids_new));
			}

			return $this;
		}


		/** Instance version of model_attr_exist
		 * @param string $attr Name of attribute
		 * @returns bool
		 */
		public function has_attr($attr)
		{
			return self::attr_exists(get_class($this), $attr);
		}


		public function __construct(array $update = array())
		{
			$model = get_class($this);

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


		public static function get_attr($model, $attr)
		{
			if ($attr === 'id') {
				$attr = self::get_id_col($model);
			}

			return parent::get_attr($model, $attr);
		}


		/** Unified name getter
		 * @param pattern
		 * @returns string
		 */
		public function get_name($pattern = null)
		{
			if (is_null($pattern)) {
				return $this->has_attr('name') ? $this->name:$this->id;
			} else {
				return soprintf($pattern, $this);
			}
		}
	}
}
