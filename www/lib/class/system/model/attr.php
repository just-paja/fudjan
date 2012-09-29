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
			'int',
			'float',
			'bool',
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
		protected function __construct($dataray = array())
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

				return $this->attr_exists($attr) ? 
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
		public function __set($name, $value)
		{
			$this->attr_exists($name) ?
				($this->data[$name] = $value):
				($this->opts[$name] = $value);

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
				$this->attr_exists($attr) ? $this->data[$attr] = $val:$this->opts[$attr] = $val;
			}

			foreach (self::$attr_types as $type) {
				if (isset($model::$attrs[$type])) {
					self::normalize_datatypes((array) $model::$attrs[$type], $type, $this->data);
				}

				if (isset(self::$merged_attrs['\\'.$model]) && isset(self::$merged_attrs['\\'.$model][$type])) {
					self::normalize_datatypes((array) self::$merged_attrs['\\'.$model][$type], $type, $this->opts);
				}
			}

			return $this;
		}


		/** Does attribute exist
		 * @param string $model Class name of desired model
		 * @param string $attr  Name of attribute
		 * @returns bool
		 */
		public static function does_attr_exist($model, $attr)
		{
			if (isset($model::$id_col) && $attr == $model::$id_col) return true;

			foreach ($model::$attrs as $type=>$temp) {
				if (!is_array($temp)) {
					unset($model::$attrs[$type]);
					continue;
				}

				if (in_array($attr, $temp)) return true;
			}

			return false;
		}


		/** Instance version of model_attr_exist
		 * @param string $attr Name of attribute
		 * @returns bool
		 */
		public function attr_exists($attr)
		{
			return self::does_attr_exist(get_class($this), $attr);
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


		/** Prepare data of a kind to be saved, mostly conversions
		 * @param array  $keys    Set of attribute names
		 * @param string $type    Type of data
		 * @param &array $dataray Object data
		 */
		public static function normalize_datatypes(array $keys, $type, &$dataray)
		{
			foreach ($keys as $attr) {
				switch ($type) {
					case 'int':
						$dataray[$attr] = isset($dataray[$attr]) ? intval($dataray[$attr]):0;
						break;

					case 'float':
						$dataray[$attr] = isset($dataray[$attr]) ? floatval($dataray[$attr]):0.0;
						break;

					case 'bool':
						$dataray[$attr] = isset($dataray[$attr]) ? !!$dataray[$attr]:false;
						break;

					case 'datetime':
						if (!isset($dataray[$attr])) {
							$dataray[$attr] = new \DateTime();
						}

						if (!($dataray[$attr] instanceof \DateTime)) {
							$dataray[$attr] = new \DateTime($dataray[$attr]);
						}
						break;

					case 'image':
						$val = &$dataray[$attr];
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
						if (any($dataray[$attr]) && is_string($dataray[$attr])) {
							$dataray[$attr] = array_filter((array) json_decode($dataray[$attr], true));
						}
						break;
				}
			}
		}

	}
}
