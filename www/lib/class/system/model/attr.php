<?

namespace System\Model
{
	abstract class Attr
	{
		// Object data
		protected $data = array();
		protected $opts = array();
		protected $errors   = array();

		// List of allowed attribute types
		protected static $attr_types = array(
			'bool',
			'int',
			'varchar',
			'text',
			'float',
			'datetime',
			'password',
			'json',
			'image',
		);

		// Registered object handlers
		static protected $obj_attrs = array('image');

		// Swap for attributes merged from related models
		static protected $merged_attrs = array();


		/** Public constructor
		 * @param array $dataray Set of data used by object
		 * @returns BasicModel
		 */
		public function __construct($dataray = array())
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
		}


		/** Attribute getter
		 * @param string $name
		 * @returns mixed
		 */
		public function __get($attr)
		{
			if (!in_array($attr, array('attrs', 'opts', 'errors'))) {
				$model = get_class($this);
				$attr == 'id' && isset($model::$id_col) && $attr = $model::$id_col;

				return $this->has_attr($attr) ?
					(isset($this->data[$attr]) ? $this->data[$attr]:null):
					(isset($this->opts[$attr]) ? $this->opts[$attr]:null);
			}

			throw new \InternalException(l('Trying to access internal private attribute: "'.$attr.'"'));
		}


		/** Attribute setter
		 * @param string $name
		 * @param mixed  $value
		 * @returns BasicModel
		 */
		public function __set($attr, $value)
		{
			if ($this->has_attr($attr)) {
				$this->data[$attr] = self::convert_attr_val(get_class($this), $attr, $value);
			} else $this->opts[$attr] = $value;

			return $this;
		}


		/** Get all object data
		 * @returns array Data ray
		 */
		public function get_data()
		{
			return $this->data;
		}


		/** Get all public non-attribute data from object
		 * @returns array
		 */
		public function get_opts()
		{
			return $this->opts;
		}


		/** Did the object encounter any internal errors
		 * @returns bool
		 */
		public function errors()
		{
			return empty($this->errors) ? false:$this->errors;
		}


		/* Report internal error
		 * @param string $msg
		 * @returns BasicModel
		 */
		private function error($msg)
		{
			$this->errors[] = $msg;
			return $this;
		}


		/** Update attributes and distribute all data into object containers
		 * @param array $update Data
		 * @returns BasicModel
		 */
		public function update_attrs(array $update)
		{
			$model = get_class($this);
			foreach($update as $attr=>$val){
				$this->__set($attr, $val);
			}

			return $this;
		}


		/** Does attribute exist
		 * @param string $model Class name of desired model
		 * @param string $attr  Name of attribute
		 * @returns bool
		 */
		public static function attr_exists($model, $attr)
		{
			return array_key_exists($attr, $model::$attrs);
		}


		/** Instance version of model_attr_exist
		 * @param string $attr Name of attribute
		 * @returns bool
		 */
		public function has_attr($attr)
		{
			return self::attr_exists(get_class($this), $attr);
		}


		/** Is attribute required
		 * @param string $attr
		 * @returns bool
		 */
		public function attr_required($attr)
		{
			$model = get_class($this);
			return in_array($attr, $model::$required);
		}


		/* Get list of model attributes
		 * @param string $model Name of model class
		 * @returns array
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
		 * @returns array
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
		 * @returns mixed Type of attribute (string) or false on failure
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
		protected static function get_attr($model, $attr)
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

			switch ($attr_data[0]) {
				case 'int':
					$val = intval($val);
					break;

				case 'float':
					$val = floatval($val);
					break;

				case 'bool':
					$val = is_null($val) ? false:!!$val;
					break;

				case 'password':
				case 'text':
				case 'varchar':
					$val = mb_substr(strval($val), 0, $attr_data['length']);
					break;

				case 'datetime':
					if (is_null($val)) {
						$val = new \DateTime();
					}

					if (!($val instanceof \DateTime)) {
						$val = new \DateTime($val);
					}
					break;

				case 'image':
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
							} elseif ($j = json_decode($val, true)) {
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

				case 'json':
					if (any($val) && is_string($val)) {
						$val = array_filter((array) json_decode($val, true));
					}
					break;
			}

			return $val;
		}

	}
}
