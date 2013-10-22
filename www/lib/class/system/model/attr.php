<?

/** Attribute handling
 * @package system
 * @subpackage models
 */
namespace System\Model
{
	/** Attribute class ihnerited widely across pwf. Gives child classes
	 * ability to store attribute data in defined type and convert these
	 * attribute types among others. This model will be your favourite.
	 * @package system
	 * @subpackage models
	 */
	abstract class Attr
	{
		/** Real attribute data */
		protected $data = array();

		/** Secondary data passed to object */
		protected $opts = array();


		/** List of allowed attribute types */
		protected static $attr_types = array(
			'bool',
			'int',
			'int_set',
			'varchar',
			'blob',
			'text',
			'float',
			'datetime',
			'password',
			'json',
			'file',
			'image',
			'gps',
			'list',
			'object',
		);

		/** Registered object handlers */
		static protected $attr_type_objects = array(
			'object' => '\Object',
			'image'  => '\System\Image',
			'file'   => '\System\File',
		);

		/** Swap for attributes merged from related models */
		static protected $merged_attrs = array();


		/** Public constructor
		 * @param array $dataray Set of data used by object
		 * @return BasicModel
		 */
		public function __construct(array $dataray = array())
		{
			$model = get_model($this);
			self::check_properties($model);

			$this->update_attrs($dataray);

			if (isset($model::$attrs['pass'])) {
				foreach ($model::$attrs['pass'] as $attr) {
					$old_attr = $attr.'_old';
					$this->$old_attr = $this->$attr;
				}
			}

			if (method_exists($this, 'construct')) {
				$this->construct($dataray);
			}

			unset($this->opts['changed']);
		}


		/** Attribute getter
		 * @param string $attr
		 * @return mixed
		 */
		public function __get($attr)
		{
			if (!in_array($attr, array('data', 'opts'))) {
				$model = get_model($this);
				$attr == 'id' && isset($model::$id_col) && $attr = $model::$id_col;

				return $this->has_attr($attr) ?
					$this->get_attr_value($attr):(isset($this->opts[$attr]) ? $this->opts[$attr]:null);
			}

			throw new \System\Error\Argument(sprintf('Trying to access internal private attribute "%s" for model "%s"', $attr, get_model($this)));
		}


		/** Attribute setter
		 * @param string $attr
		 * @param mixed  $value
		 * @return BasicModel
		 */
		public function __set($attr, $value)
		{
			if ($this->has_attr($attr)) {
				$def = self::get_attr(get_model($this), $attr);

				if (!isset($def['writeable']) || $def['writeable']) {
					$null_error = false;

					if (is_null($value)) {

						if (empty($def['is_null'])) {
							if (any($def['default'])) {
								$value = $def['default'];
							}
						}
					}

					$this->data[$attr] = self::convert_attr_val(get_model($this), $attr, $value);
					$this->changed = true;
				} else throw new \System\Error\Model(sprintf("Attribute '%s' is not publicly writeable for model '%s'.", $attr, get_model($this)));
			} else $this->opts[$attr] = $value;

			return $this;
		}


		/** Get all object data
		 * @return array Data ray
		 */
		public function get_data()
		{
			return $this->data;
		}


		/** Return reference to object data store
		 * @return &array
		 */
		public function &get_data_ref()
		{
			return $this->data;
		}


		/** Get all public non-attribute data from object
		 * @return array
		 */
		public function get_opts()
		{
			return $this->opts;
		}


		/** Get reference to non-attr data store
		 * @return &array
		 */
		public function &get_opts_ref()
		{
			return $this->opts;
		}


		/** Update attributes and distribute all data into object containers
		 * @param array $update Data
		 * @return BasicModel
		 */
		public function update_attrs(array $update)
		{
			foreach ($update as $attr=>$val) {
				$this->__set($attr, $val);
			}

			return $this;
		}


		/** Does attribute exist
		 * @param string $model Class name of desired model
		 * @param string $attr  Name of attribute
		 * @return bool
		 */
		public static function attr_exists($model, $attr)
		{
			if ($model && $attr) {
				return array_key_exists($attr, $model::$attrs);
			} else return false;
		}


		/** Instance version of model_attr_exist
		 * @param string $attr Name of attribute
		 * @return bool
		 */
		public function has_attr($attr)
		{
			return self::attr_exists(get_model($this), $attr);
		}


		/** Is attribute required
		 * @param string $attr
		 * @return bool
		 */
		public function attr_required($attr)
		{
			$model = get_model($this);
			return in_array($attr, $model::$required);
		}


		/** Get list of model attributes
		 * @param string $model Name of model class
		 * @return array
		 */
		public static function get_model_attr_list($model)
		{
			$attrs = array();

			foreach ($model::$attrs as $attr=>$def) {
				$attrs[] = $attr;
			}

			return $attr;
		}


		/** Get list of model attributes
		 * @param string $model Name of model class
		 * @return array
		 */
		public static function get_model_attrs($model)
		{
			$attrs = array();

			foreach ($model::$attrs as $attr=>$def) {
				if (empty($def['is_fake'])) {
					$attrs[$attr] = $def;
				}
			}

			return $attrs;
		}


		/** Gets definition of model attributes
		 * @param string $model Name of model
		 */
		public static function get_attr_def($model)
		{
			return $model::$attrs;
		}


		/** Get type of attribute
		 * @param string $model Name of model class
		 * @param string $attr  Name of attribute
		 * @return mixed Type of attribute (string) or false on failure
		 */
		public static function get_attr_type($model, $attr)
		{
			if (self::attr_exists($model, $attr)) {
				return $model::$attrs[$attr][0];
			} else throw new \System\Error\Model(sprintf('Attribute "%s" of model "%s" does not exist.', $attr, $model));
		}


		/** Get attr definition
		 * @param string $model
		 * @param string $attr
		 * @return array
		 */
		public static function get_attr($model, $attr)
		{
			if (self::attr_exists($model, $attr)) {
				$attr_data = &$model::$attrs[$attr];

				if (in_array($attr_data[0], array('varchar', 'password'))) {
					if (!isset($attr_data['length'])) $attr_data['length'] = 255;
				}

				if ($attr_data[0] === 'text') {
					if (!isset($attr_data['length'])) $attr_data['length'] = 65535;
				}

				return $attr_data;
			}

			throw new \System\Error\Model(sprintf('Attribute "%s" of model "%s" does not exist!', $attr, $model));
		}


		/** Prepare data of a kind to be saved, mostly conversions
		 * @param string $model Name of model
		 * @param string $attr  Name of attribute
		 * @param mixed  $val   Value to check and fix
		 * @return mixed Fixed value
		 */
		public static function convert_attr_val($model, $attr, $val = null)
		{
			$attr_data = $model::get_attr($model, $attr);

			if (isset($attr_data['is_null']) && $attr_data['is_null'] && is_null($val)) {
				return $val = null;
			}

			switch ($attr_data[0]) {
				case 'int':
				{
					$val = intval($val);
					break;
				}


				case 'float':
				{
					$val = floatval($val);
					break;
				}


				case 'bool':
				{
					$val = is_null($val) ? false:!!$val;
					break;
				}


				case 'password':
				case 'text':
				case 'varchar':
				{
					$val = mb_substr(strval($val), 0, $attr_data['length']);
					break;
				}


				case 'datetime':
				{
					$is_null = !isset($attr_data['is_null']) || !$attr_data['is_null'];

					if (!($val instanceof \DateTime)) {
						if (any($val)) {
							$val = new \DateTime($val);
						}

						if (!$is_null && !$val) {
							$val = new \DateTime();
						}
					}
					break;
				}


				case 'image':
				case 'file':
				{
					if ($attr_data[0] == 'image') {
						$cname = '\System\Image';
					} else {
						$cname = '\System\File';
					}

					if (any($val)) {
						if (is_object($val)) {
							if (!($val instanceof $cname)) {
								throw new \System\Error\Model(sprintf('Value for attribute "%s" of model "%s" should be instance of "%s". Instance of "%s" was given.', $attr, $model, $cname, get_class($val)));
							}
						} elseif (is_array($val)) {
							$val = new $cname($val);
						} elseif (is_string($val)) {
							$val_json = str_replace("\\", "", $val);

							if ($j = \System\Json::decode($val_json, true)) {
								$val = new $cname($j);
							} else {
								$val = $cname::from_path($val);
							}
						}
					} else {
						$val = null;
					}

					break;
				}


				case 'object':
				{
					if ($val) {
						if (isset($attr_data['model'])) {
							if (!($val instanceof $attr_data['model'])) {
								throw new \System\Error\Argument(sprintf("Value must be instance of '%s' for attribute '%s'", $attr_data['model'], $attr));
							}
						} else throw new \System\Error\Argument(sprintf("Attribute '%s' of model '%s' must have model defined!", $attr, $model));
					}
					break;
				}


				case 'json':
				{
					if (any($val) && is_string($val)) {
						$val = array_filter((array) \System\Json::decode($val));
					}
					break;
				}


				case 'int_set':
				{
					if (any($val)) {
						if (is_array($val)) {
							$val = array_map('intval', array_filter($val));
						} else {
							$val = array_map('intval', explode(',', $val));
						}
					} else $val = array();

					break;
				}


				case 'point':
				{
					if (any($val) && !($val instanceof \System\Gps)) {
						if (is_array($val)) {
							$val = \System\Gps::from_array($val);
						} elseif (strpos($val, 'POINT(') === 0) {
							$val = \System\Gps::from_sql($val);
						} else {
							$val = new \System\Gps();
						}
					}

					break;
				}


				case 'video_youtube':
				{
					if (any($val) && !($val instanceof \System\Video\Youtube)) {
						if (is_string($val)) {

							($vid = \System\Video\Youtube::from_url($val)) ||
							($vid = \System\Video\Youtube::from_id($val));
							$val = $vid;

						} else throw new \System\Error\Format('Cannot create Youtube video object from "'.gettype($val).'".');
					}
				}


				case 'list':
				{
					if (!is_array($val)) {
						$val = (array) $val;
					}
				}
			}

			return $val;
		}


		/** Did object change since it's construction?
		 * @param string $status Change status to this
		 * @return bool
		 */
		public function changed($status = null)
		{
			if (!is_null($status)) {
				$this->changed = !!$status;
			}

			return !!$this->changed;
		}


		/** Get translated attribute name
		 * @param string $attr
		 * @return string
		 */
		public function get_attr_name($attr)
		{
			return self::get_model_attr_name(get_model($this), $attr);
		}


		/** Get translated attribute description
		 * @param string $attr
		 * @return string
		 */
		public function get_attr_desc($attr)
		{
			return self::get_model_attr_desc(get_model($this), $attr);
		}


		/** Get translated model name
		 * @param bool $plural
		 * @return string
		 */
		public function get_model_name($plural = false)
		{
			return self::get_model_model_name($model, $plural);
		}


		/** Get attribute value
		 * @param string $attr
		 * @return mixed
		 */
		public function get_attr_value($attr)
		{
			if (isset($this->data[$attr])) {
				return $this->data[$attr];
			} else {
				$this->__set($attr, null);
				return $this->data[$attr];
			}
		}


		/** Encode object into json
		 * @param bool [true] $encode Encode into JSON string
		 * @return string|array
		 */
		public function to_json($encode=true)
		{
			$data = \System\Template::to_json($this->get_data(), false);
			return $encode ? \System\Template::to_json($data):$data;
		}


		/** Check static model class properties
		 * @param string $model
		 */
		public static function check_properties($model)
		{
			if (!isset($model::$attrs)) {
				$parent = get_parent_class($model);

				if ($parent) {
					return self::check_properties($model);
				} else throw new \System\Error\Model(sprintf("You must define property 'protected static \$attrs' to model '%s' to inherit attr model properly.", $model));
			}
		}


		/** Convert attr model to html
		 * @param \System\Template\Renderer $ren
		 * @return string
		 */
		public function to_html(\System\Template\Renderer $ren)
		{
			return sprintf('%s', $ren->locales()->trans_class_name(get_class($this)));
		}


		/** Convert model to string
		 * @return string
		 */
		public function __toString()
		{
			return sprintf('[%s]', \System\Loader::get_model_from_class(get_class($this)));
		}
	}
}
