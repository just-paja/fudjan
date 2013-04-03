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
			'image',
			'gps',
			'list',
		);

		/** Registered object handlers */
		static protected $obj_attrs = array('image');

		/** Swap for attributes merged from related models */
		static protected $merged_attrs = array();


		/** Public constructor
		 * @param array $dataray Set of data used by object
		 * @return BasicModel
		 */
		public function __construct(array $dataray = array())
		{
			$model = get_class($this);
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

			$this->changed = false;
		}


		/** Attribute getter
		 * @param string $name
		 * @return mixed
		 */
		public function __get($attr)
		{
			if (!in_array($attr, array('data', 'opts'))) {
				$model = get_class($this);
				$attr == 'id' && isset($model::$id_col) && $attr = $model::$id_col;

				return $this->has_attr($attr) ?
					$this->get_attr_value($attr):(isset($this->opts[$attr]) ? $this->opts[$attr]:null);
			}

			throw new \System\Error\Argument(sprintf('Trying to access internal private attribute "%s" for model "%s"', $attr, get_class($this)));
		}


		/** Attribute setter
		 * @param string $name
		 * @param mixed  $value
		 * @return BasicModel
		 */
		public function __set($attr, $value)
		{
			if ($this->has_attr($attr)) {
				$null_error = false;

				if (is_null($value)) {
					$def = self::get_attr(get_class($this), $attr);

					if (empty($def['is_null'])) {
						if (any($def['default'])) {
							$value = $def['default'];
						}
					}
				}

				$this->data[$attr] = self::convert_attr_val(get_class($this), $attr, $value);
				$this->changed = true;
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


		/** Get all public non-attribute data from object
		 * @return array
		 */
		public function get_opts()
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
			return array_key_exists($attr, $model::$attrs);
		}


		/** Instance version of model_attr_exist
		 * @param string $attr Name of attribute
		 * @return bool
		 */
		public function has_attr($attr)
		{
			return self::attr_exists(get_class($this), $attr);
		}


		/** Is attribute required
		 * @param string $attr
		 * @return bool
		 */
		public function attr_required($attr)
		{
			$model = get_class($this);
			return in_array($attr, $model::$required);
		}


		/* Get list of model attributes
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


		/* Get list of model attributes
		 * @param string $model Name of model class
		 * @return array
		 */
		public static function get_model_attrs($model)
		{
			return self::get_attrs($model::$attrs);
		}


		/** Join all attributes into single array (helper)
		 * @param  array $attributes
		 * @return array List of attributes
		 */
		public static function get_attrs($attrs)
		{
			$temp = array();
			foreach($attrs as $attr_type) {
				$temp = array_merge($temp, (array) $attr_type);
			}

			return $temp;
		}


		/** Gets definition of model attributes
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
			foreach ($model::$attrs as $type=>$attr_set) {
				if (in_array($attr, $attr_set)) {
					return $type;
				}
			}

			return false;
		}


		/** Get attr definition
		 * @param string $model
		 * @param string $attribute
		 */
		public static function get_attr($model, $attr)
		{
			$attr_data = &$model::$attrs[$attr];

			if (in_array($attr_data[0], array('varchar', 'password'))) {
				if (!isset($attr_data['length'])) $attr_data['length'] = 255;
			}

			if ($attr_data[0] === 'text') {
				if (!isset($attr_data['length'])) $attr_data['length'] = 65535;
			}

			return $attr_data;
		}


		/** Prepare data of a kind to be saved, mostly conversions
		 * @param array  $keys    Set of attribute names
		 * @param string $type    Type of data
		 * @param &array $dataray Object data
		 */
		public static function convert_attr_val($model, $attr, $val = null)
		{
			$attr_data = self::get_attr($model, $attr);

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
				{
					if (!($val instanceof \System\Image)) {

						if (is_array($val) && is_array($val['name'])) {
							foreach ($val as &$d) {
								if (is_array($d)) {
									$d = reset($d);
								}
							}
						}

						if (any($val) && !is_array($val) || is_array($val) && (empty($val['src']) || (any($val['src']) && $val['src'] != 'actual'))) {

							$val = str_replace("\\", "", $val);
							if (is_array($val)) {
								$val = new \System\Image($val);
							} elseif ($j = \System\Json::decode($val, true)) {
								$val = \System\Image::from_json($val);
							} elseif($val) {
								$val = \System\Image::from_path($val);
							} else {
								$val = \System\Image::from_scratch();
							}

						} else {
							$val = \System\Image::from_scratch();
						}
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


		public function changed($status = null)
		{
			if (!is_null($status)) {
				$this->changed = !!$status;
			}

			return $this->changed;
		}


		public function get_attr_name($attr)
		{
			return self::get_model_attr_name(get_class($this), $attr);
		}


		public function get_attr_desc($attr)
		{
			return self::get_model_attr_desc(get_class($this), $attr);
		}


		public function get_model_name($plural = false)
		{
			return self::get_model_model_name($model, $plural);
		}


		public static function get_model_attr_name($model, $attr)
		{
			return l('attr_'.\System\Loader::get_link_from_class($model).'_'.$attr);
		}


		public static function get_model_attr_desc($model, $attr)
		{
			return l('attr_'.\System\Loader::get_link_from_class($model).'_'.$attr.'_desc');
		}


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
	}
}
