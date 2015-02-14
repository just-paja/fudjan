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
		/** Initial attribute data */
		protected $data_initial = array();

		/** Real attribute data */
		protected $data = array();

		/** Secondary data passed to object */
		protected $opts = array();


		protected static $attrs = array();


		/** List of allowed attribute types */
		protected static $attr_types = array(
			'bool',
			'int',
			'int_set',
			'varchar',
			'blob',
			'text',
			'float',
			'date',
			'datetime',
			'time',
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

			if (any($dataray)) {
				$this->data_initial = $this->data;
			}

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
			$model = get_class($this);

			if ($attr == 'id' && isset($this::$id_col)) {
				$attr = $this::$id_col;
			}

			if ($this::has_attr($attr)) {
				return $this->get_attr_value($attr);
			}

			return isset($this->opts[$attr]) ? $this->opts[$attr]:null;
		}


		/** Attribute setter
		 * @param string $attr
		 * @param mixed  $value
		 * @return BasicModel
		 */
		public function __set($attr, $value)
		{
			if ($this->has_attr($attr)) {
				$def = $this::get_attr($attr);

				if (!isset($def['writeable']) || $def['writeable']) {
					$null_error = false;
					$this->data[$attr] = self::convert_attr_val(get_model($this), $attr, $value);
					$this->changed = true;
				} else throw new \System\Error\Model(sprintf("Attribute '%s' is not publicly writeable for model '%s'.", $attr, get_model($this)));
			} else $this->opts[$attr] = $value;

			return $this;
		}


		public function set_default_value($attr)
		{
			$def = $this::get_attr($attr);

			if (isset($def['default'])) {
				$this->$attr = $def['default'];
			} else {
				$this->$attr = null;
			}
		}


		/**
		 * Get all object data
		 *
		 * @return array Object data
		 */
		public function get_data()
		{
			return $this->data;
		}


		/**
		 * Return reference to object data store
		 *
		 * @return &array
		 */
		public function &get_data_ref()
		{
			return $this->data;
		}


		/**
		 * Get all public non-attribute data from object
		 *
		 * @return array
		 */
		public function get_opts()
		{
			return $this->opts;
		}


		/**
		 * Get reference to non-attr data store
		 *
		 * @return &array
		 */
		public function &get_opts_ref()
		{
			return $this->opts;
		}


		/**
		 * Update attributes and distribute all data into object containers
		 *
		 * @param array $update Data
		 * @return BasicModel
		 */
		public function update_attrs(array $update)
		{
			if ($update && empty($this->data_initial)) {
				$this->data_initial = $this->data;
			}

			foreach ($update as $attr=>$val) {
				$this->__set($attr, $val);
			}

			return $this;
		}


		/**
		 * Does attribute exist
		 *
		 * @param string $attr Name of attribute
		 * @return bool
		 */
		public static function has_attr($attr)
		{
			$cname = get_called_class();
			return array_key_exists($attr, $cname::$attrs);
		}


		/**
		 * Is attribute required
		 *
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


		/**
		 * Gets definition of model attributes
		 *
		 * @return array
		 */
		public static function get_attr_def()
		{
			$model = get_called_class();
			return $model::$attrs;
		}


		/**
		 * Get type of attribute
		 *
		 * @param string $model Name of model class
		 * @param string $attr  Name of attribute
		 * @return mixed Type of attribute (string) or false on failure
		 */
		public static function get_attr_type($attr)
		{
			$model = get_called_class();
			$attr  = $model::get_attr($attr);

			if (isset($model::$attrs[$attr]['type'])) {
				$model::$attrs[$attr][0] = $model::$attrs[$attr]['type'];
			}

			return $model::$attrs[$attr][0];
		}


		/** Get attr definition
		 * @param string $model
		 * @param string $attr
		 * @return array
		 */
		public static function get_attr($attr)
		{
			$model = get_called_class();

			if ($model::has_attr($attr)) {
				$attr_data = &$model::$attrs[$attr];
				$type = $model::get_attr_type($attr);

				if (in_array($type, array('varchar', 'password'))) {
					if (!isset($attr_data['length'])) $attr_data['length'] = 255;
				}

				if ($type === 'text') {
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
			$attr_data = $model::get_attr($attr);

			if (isset($attr_data['is_null']) && $attr_data['is_null'] && is_null($val)) {
				return $val = null;
			}

			switch ($attr_data[0]) {
				case 'int':
				{
					$val = filter_var($val,  FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
					break;
				}


				case 'float':
				{
					$val = filter_var($val,  FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
					break;
				}


				case 'bool':
				{
					$val = is_null($val) ? false:!!$val;
					break;
				}


				case 'password':
				case 'text':
				case 'time':
				case 'varchar':
				{
					$val = mb_substr(strval($val), 0, isset($attr_data['length']) ? $attr_data['length']:255);
					break;
				}


				case 'date':
				case 'datetime':
				{
					$is_null = isset($attr_data['is_null']) && $attr_data['is_null'];

					if (!($val instanceof \DateTime)) {
						if (any($val)) {
							if ($val == '0000-00-00 00:00:00') {
								$val = null;
							} else {
								$val = \DateTime::createFromFormat('Y-m-d H:i:s', $val, new \DateTimeZone(\System\Settings::get('locales', 'timezone')));
							}
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
								throw new \System\Error\Argument(sprintf("Value must be instance of '%s'", $attr_data['model']), $model, $attr, is_object($val) ? get_class($val):gettype($val));
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


		/** Get options for model if defined
		 * @param string $model
		 * @param string $attr
		 * @return false|array
		 */
		public static function get_model_attr_options($model, $attr)
		{
			if ($model::has_attr($attr)) {
				if (isset($model::$attrs[$attr]['options'])) {
					return $model::$attrs[$attr]['options'];
				} else return false;
			} else throw new \System\Error\Model(sprintf('Attr %s does not exist.', $attr));
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


		/** Get attribute value
		 * @param string $attr
		 * @return mixed
		 */
		public function get_attr_value($attr)
		{
			if (!isset($this->data[$attr])) {
				$this->set_default_value($attr);
			}

			return $this->data[$attr];
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


		/** Convert model to string
		 * @return string
		 */
		public function __toString()
		{
			return sprintf('[%s]', \System\Loader::get_model_from_class(get_class($this)));
		}


		public function to_object()
		{
			return self::to_object_batch($this->get_data(), $this);
		}


		public static function to_object_batch($data, $obj = null, $key = null)
		{
			if (is_array($data)) {
				foreach ($data as $key=>$value) {
					$data[$key] = self::to_object_batch($value, $obj, $key);
				}
			} else if (is_object($data)) {
				if (method_exists($data, 'to_object')) {
					$empty = method_exists($data, 'is_empty') && $data->is_empty();

					if (!$empty) {
						$data = $data->to_object();
					} else {
						$data = null;
					}
				} else if ($data instanceof \DateTime) {
					$data = $data->format('c');
				}
			}


			if (is_null($data) && $obj && $key) {
				try {
					$attr = $obj::get_attr($key);
				} catch (\System\Error\Model $e) {
					$attr = null;
				}

				if ($attr) {
					if (isset($attr['default'])) {
						$data = self::to_object_batch(self::convert_attr_val($obj, $key, $attr['default']), $obj);
					}
				}
			}

			return $data;
		}
	}
}
