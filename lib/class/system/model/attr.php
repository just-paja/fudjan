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
		const USE_INITIAL_DATA = false;


		/** Initial attribute data */
		protected $data_initial = array();

		/** Real attribute data */
		protected $data = array();

		/** Secondary data passed to object */
		protected $opts = array();

		protected static $resolved_models = array();

		/** Has the initial check been called */
		protected static $is_type_checked = false;

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
		protected static $attr_type_objects = array(
			'object' => '\Object',
			'image'  => '\System\Image',
			'file'   => '\System\File',
		);

		/** Swap for attributes merged from related models */
		protected static $merged_attrs = array();


		/**
		 * Public constructor
		 *
		 * @param array $dataray Set of data used by object
		 * @return System\Model\Attr
		 */
		public function __construct(array $dataray = array())
		{
			$model = get_model($this);
			$model::check_model();

			$this->update_attrs($dataray);

			if (static::USE_INITIAL_DATA && any($dataray)) {
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


		/**
		 * Attribute getter
		 *
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


		/**
		 * Attribute setter
		 *
		 * @param string $attr
		 * @param mixed  $value
		 * @return System\Model\Attr
		 */
		public function __set($attr, $value)
		{
			if ($this->has_attr($attr)) {
				$def = $this::get_attr($attr);

				if (!array_key_exists('writeable', $def) || $def['writeable']) {
					$null_error = false;
					$this->data[$attr] = $this::convert_attr_val($attr, $value);
					$this->changed = true;
				} else throw new \System\Error\Model(sprintf("Attribute is readonly.", \System\Loader::get_model_from_class(get_class($this)).'.'.$attr));
			} else $this->opts[$attr] = $value;

			return $this;
		}


		/**
		 * Add attribute to this model
		 *
		 * @param string $attr
		 * @param array  $def  Attribute definition
		 * @return void
		 */
		public static function add_attr($attr, array $def)
		{
			static::$attrs[$attr] = $def;
		}


		/**
		 * Set default value for attribute if applicable
		 *
		 * @return void
		 */
		public function set_default_value($attr)
		{
			$def = $this::get_attr($attr);

			if (isset($def['default'])) {
				$this->data[$attr] = $this::convert_attr_val($attr, $def['default']);
			} else {
				$this->data[$attr] = null;
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
			if (static::USE_INITIAL_DATA && $update && empty($this->data_initial)) {
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
			if (is_string($attr) || is_numeric($attr)) {
				return array_key_exists($attr, static::$attrs);
			}

			throw new \System\Error\Argument('First argument passed to has_attr must be string.', $attr);
		}


		/**
		 * Is attribute required
		 *
		 * @param string $attr
		 * @return bool
		 */
		public static function attr_required($attr)
		{
			return in_array($attr, static::$required);
		}


		/**
		 * Get list of model attributes
		 *
		 * @return array
		 */
		public static function get_attr_list()
		{
			$attrs = static::get_attr_def();
			$list  = array();

			foreach ($attrs as $attr=>$def) {
				$list[] = $attr;
			}

			return $list;
		}


		/**
		 * Gets definition of model attributes
		 *
		 * @return array
		 */
		public static function get_attr_def()
		{
			return static::$attrs;
		}


		/**
		 * Get type of attribute
		 *
		 * @param string $attr  Name of attribute
		 * @return string
		 */
		public static function get_attr_type($attr)
		{
			$def = static::get_attr($attr);
			return $def['type'];
		}


		/**
		 * Get attr definition
		 *
		 * @param string $attr
		 * @return array
		 */
		public static function get_attr($attr)
		{
			static::check_model();

			if (static::has_attr($attr)) {
				return static::$attrs[$attr];
			}

			throw new \System\Error\Model(sprintf('Attribute "%s" of model "%s" does not exist!', $attr, get_called_class()));
		}


		/**
		 * Prepare data of a kind to be saved, mostly conversions
		 *
		 * @param string $attr  Name of attribute
		 * @param mixed  $val   Value to check and fix
		 * @return mixed Fixed value
		 */
		public static function convert_attr_val($attr, $val = null)
		{
			$attr_data = static::get_attr($attr);

			if (isset($attr_data['is_null']) && $attr_data['is_null'] && is_null($val)) {
				return $val = null;
			}

			switch ($attr_data['type']) {
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
					if ($attr_data['type'] == 'image') {
						$cname = '\System\Image';
					} else {
						$cname = '\System\File';
					}

					if (any($val)) {
						if (is_object($val)) {
							if (!($val instanceof $cname)) {
								throw new \System\Error\Model(sprintf('Value for attribute "%s" of model "%s" should be instance of "%s". Instance of "%s" was given.', $attr, get_called_class(), $cname, get_class($val)));
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
								throw new \System\Error\Argument(sprintf("Value must be instance of '%s'", $attr_data['model']), get_called_class(), $attr, is_object($val) ? get_class($val):gettype($val));
							}
						} else throw new \System\Error\Argument(sprintf("Attribute '%s' of model '%s' must have model defined!", $attr, get_called_class()));
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


		/**
		 * Get options for model if defined
		 *
		 * @param string $attr
		 * @return false|array
		 */
		public static function get_attr_options($attr)
		{
			$attr = static::get_attr($attr);

			if (isset($attr['options'])) {
				return $attr['options'];
			}

			return null;
		}


		/**
		 * Did object change since it's construction?
		 *
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


		/**
		 * Get attribute value
		 *
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


		/**
		 * Check if model is valid before operating
		 *
		 * @return void
		 */
		public static function check_model()
		{
			$model = get_called_class();

			if (empty(static::$resolved_models[$model])) {
				if (!isset($model::$attrs)) {
					throw new \System\Error\Model(sprintf("You must define property 'protected static \$attrs' to model '%s' to inherit attr model properly.", $model));
				}

				$src = &$model::$attrs;

				foreach ($src as $name=>$def) {
					$attr = &$src[$name];

					if (!isset($attr['type'])) {
						throw new \System\Error\Model('You must define type of attribute', $model.'.'.$name);
					}

					if ($attr['type'] == 'varchar' && !isset($attr['length'])) {
						$attr['length'] = 255;
					}

					if ($attr['type'] == 'text' && !isset($attr['length'])) {
						$attr['length'] = 65535;
					}
				}

				static::$resolved_models[$model] = true;
			}
		}


		/**
		 * Convert model to string
		 *
		 * @return string
		 */
		public function __toString()
		{
			return sprintf('[%s]', \System\Loader::get_model_from_class(get_class($this)));
		}


		/**
		 * Convert object to array
		 *
		 * @return array
		 */
		public function to_object()
		{
			return self::to_object_batch($this->get_data(), $this);
		}


		/**
		 * Convert data object to array or simple type
		 *
		 * @param mixed  $data
		 * @param object $obj
		 * @param string $key
		 * @return mixed
		 */
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
						$data = self::to_object_batch($obj::convert_attr_val($key, $attr['default']), $obj);
					}
				}
			}

			return $data;
		}
	}
}
