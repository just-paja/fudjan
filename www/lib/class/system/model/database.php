<?

namespace System\Model
{
	abstract class Database extends Callback
	{
		// Replace chars
		private static $bad_chars  = array(' ', '_', '--');
		private static $good_chars = array('-', '-', '-', '');

		private static $strictly_bad_chars = array('-');
		private static $strictly_good_chars = array('_');

		// Basic
		static protected $table;
		static protected $id_col;
		static protected $tables_generated = array();
		static protected $id_cols_generated = array();

		// Relations
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
			return $attr == self::get_id_col($model) || parent::attr_exists($model, $attr);
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


		/* Get all models of a class
		 * @param   string $model Class name desired model
		 * @param   array  $conds Set of conditions
		 * @param   array  $opts  Set of query options
		 * @param   array  $joins Set of query joins
		 * @returns array         Set of matched models
		 */
		public static function get_all($model, array $conds = array(), array $opts = array(), array $joins = array())
		{
			if (!$model || !class_exists($model)) throw new \FatalException(sprintf(_('Model not found: %s'), var_export($model, true)));

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

							$attrs_to_merge[] = array(self::get_table($jmodel), "USING(".($jmodel::$id_col).")", 'extension_'.$k, $attr_def);
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
					 (isset($model::$has_many)   && array_key_exists($attr, $model::$has_many))
				|| (isset($model::$has_one)    && array_key_exists($attr, $model::$has_one))
				|| (isset($model::$belongs_to) && array_key_exists($attr, $model::$belongs_to));
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
				if (array_key_exists($attr, $model::$has_many))       $type = 'has-many';
				elseif (array_key_exists($attr, $model::$has_one))    $type = 'has-one';
				elseif (array_key_exists($attr, $model::$belongs_to)) $type = 'belongs-to';
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
			if (self::attr_is_rel($this, $name)) {
				$this->$name = $value;
				return $this;
			}

			if ($name == 'id' || $name == self::get_id_col(get_class($this))) {
				$this->data[self::get_id_col(get_class($this)] = intval($value);
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
					return $this->data[self::get_id_col(get_class($this))];
				}

				return parent::__get($attr);
			}

		}

		/** Preload relation data
		 * @returns void
		 */
		public function get_rels()
		{
			$model = get_class($this);
			if (isset($model::$has_many)) {
				foreach ($model::$has_many as $rel=>$rel_attrs) {
					$this->get_rel($model, $rel, 'has-many');
				}
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

					$join_alias = 'jt_'.$rel;
					$rel_attrs = $model::$has_many[$rel];
					$helper = get_all($rel_attrs['model'], array(), array());

					if (!empty($rel_attrs['join-table']) || $rel_attrs['join-table'] = self::get_table($rel_attrs['model'])) {
						$helper->join($rel_attrs['join-table'], "USING(".$rel_attrs['model']::$id_col.")", $join_alias);
					}

					self::attr_exists($rel_attrs['model'], 'order') && $helper->add_opts(array("order-by" => "`t0`.".'`order` ASC'));

					$helper->where(array("`".$join_alias."`.`".$model::$id_col."` = ". intval($this->id)));
					$helper->assoc_with($rel_attrs['model']);

					$this->id ? $helper->cancel_ignore():$helper->ignore_query(array());
					return $helper;

				} elseif ($type == 'has-one') {

					$rel_attrs = $model::$has_one[$rel];
					$idc = $model::$id_col;
					if (any($rel_attrs['foreign-key'])) {
						$conds = array($rel_attrs['foreign-key'] => $this->id);
					} else {
						$conds = array($idc => $this->$idc);
					}
					if ($rel_attrs['conds']) {
						$conds = array_merge($rel_attrs['conds'], $conds);
					}

					$this->$rel = get_first($rel_attrs['model'], $conds)->fetch();
					$this->opts[$rel.'-fetched'] = true;

				} elseif ($type == 'belongs-to') {

					$rel_attrs = $model::$belongs_to[$rel];
					$idl = isset($rel_attrs['local-key']) ? $rel_attrs['local-key']:$rel_attrs["model"]::$id_col;
					$idc = isset($rel_attrs['foreign-key']) ? $rel_attrs['foreign-key']:$rel_attrs["model"]::$id_col;
					$conds = array($idc => $this->$idl);

					if (any($rel_attrs['conds'])) {
						$conds = array_merge($rel_attrs['conds'], $conds);
					}

					$this->$rel = get_first($rel_attrs['model'], $conds)->fetch();
					$this->opts[$rel.'-fetched'] = true;

				}
			}

			return $this->$rel;
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
						$this->errors[] = 'missing-attr-'.$attr;
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
			if (any($this->before_save)) {
				self::run_tasks($this, $this->before_save);
			}

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
							if (is_object($this->$name) && $this->$name->allow_save()) {
								$this->$name->save();
							} elseif ($this->$name->is_to_be_deleted()) {
								$this->data[$name] = null;
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

				if ($this->id) {
					\System\Database::simple_update(self::get_table($model), $model::$id_col, $this->id, $data);
				} else {
					$id = \System\Database::simple_insert(self::get_table($model), $data);
					if ($id) {
						return $this->update_attrs(array($model::$id_col => $id));
					} else {
						$this->errors[] = 'save-failed';
					}
				}
			} else {
				$this->errors[] = 'missing-required-attrs';
			}

			if (any($this->after_save)) {
				self::run_tasks($this, $this->after_save);
			}

			return $this;
		}


		/** Prepare data to be saved (ReJSON)
		 * @returns void
		 */
		protected static function prepare_data($model, array &$data)
		{
			if (any($model::$attrs['json'])) {
			foreach ($model::$attrs['json'] as $attr) {
					isset($data[$attr]) && $data[$attr] = json_encode($data[$attr]);
				}
			}
		}


		/** Delete object from database
		 * @returns bool
		 */
		public function drop()
		{
			$model = get_class($this);
			return \System\Query::simple_delete(self::get_table($model), array($model::$id_col => $this->id));
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
				self::model_attr_exists($model, $attr) && $conds[$attr] = $val;
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
				$this->update_attrs(get_first($model, array($model::$id_col => $this->id))->assoc_with_no_model()->fetch());
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


		public static function get_relation_def($model)
		{
			$relations = array();

			if (isset($model::$has_many)) {
				foreach ($model::$has_many as $rel_name => $rel_def) {
					$rel_def['type'] = 'has_many';
					$relations[$rel_name] = $rel_def;
				}
			}

			return $relations;
		}


		/* Get list of model attributes
		 * @param string $model Name of model class
		 * @returns array
		 */
		public static function get_model_attr_list($model)
		{
			$attrs = array(self::get_id_col($model));

			foreach ($model::$attrs as $attr=>$def) {
				$attrs[] = $attr;
			}

			$attrs[] = 'created_at';
			$attrs[] = 'updated_at';

			return $attrs;
		}
	}
}
