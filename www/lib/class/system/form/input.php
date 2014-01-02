<?

namespace System\Form
{
	class Input extends \System\Form\Element
	{
		protected static $attrs = array(
			"id"           => array("varchar"),
			"name"         => array("varchar"),
			"type"         => array("varchar"),
			"label"        => array("varchar"),
			"kind"         => array("varchar"),
			"content"      => array("text"),
			"desc"         => array("varchar"),
			"placeholder"  => array('varchar'),
			"maxlen"       => array('int'),
			"step"         => array('float'),
			"min"          => array('int'),
			"max"          => array('int'),
			"required"     => array('bool'),
			"checked"      => array('bool'),
			"autocomplete" => array('bool'),
			"value"        => array('mixed'),
			"options"      => array('array'),
			"class"        => array('array'),

			// Widget specific
			'ident' => array("varchar"),
		);

		protected static $required = array(
			'name', 'kind',
		);

		protected static $kinds = array('input', 'textarea', 'select', 'button');
		protected static $kinds_content_value = array('textarea');
		protected static $kinds_no_label = array('button');
		protected static $types = array(
			'textarea',
			'select',
			'text',
			'number',
			'date',
			'file',
			'range',
			'url',
			'email',
			'hidden',
			'button',
			'submit',
			'password',
		);


		protected $tools = array();


		protected function construct()
		{
			!$this->type && self::get_default_type();
			!$this->kind && self::get_default_kind();
			$this->kind = in_array($this->type, self::$kinds) ?
				$this->type:
				(in_array($this->kind, self::$kinds) ? $this->kind:self::get_default_kind());
			!$this->id && $this->id = 'field_'.$this->name;

			$this->type == 'submit' && $this->kind = 'button';

			if (!$this->name) {
				throw new \System\Error\Form('You must enter input name!', $this->type);
			}
		}


		public static function is_allowed_type($type)
		{
			return in_array($type, self::$types);
		}


		public static function is_allowed_kind($kind)
		{
			return in_array($kinds, self::$kinds);
		}


		public static function get_default_type()
		{
			return self::$types[0];
		}


		public static function get_default_kind()
		{
			return self::$kinds[0];
		}


		public function is_value_content()
		{
			return in_array($this->kind, self::$kinds_content_value);
		}


		public function has_label()
		{
			$has = !in_array($this->kind, self::$kinds_no_label);

			if ($has && $this->parent) {
				if ($this->parent->get_tool_count() <= 1) {
					$has = false;
				}
			}

			return $has;
		}


		public function is_valid()
		{
			$valid = true;
			$value = $this->form()->input_value($this->name);

			if ($this->required && !$value) {
				$this->form()->report_error($this->name, 'form_error_input_empty');
				$valid = false;
			}

			if ($valid && $value) {
				if ($this->options) {
					if (!is_object($value)) {
						$value = (array) $value;
					} else {
						$value = array($value);
					}

					if (empty($value)) {
						if (!$this->required) {
							$valid = true;
						} else {
							$this->form()->report_error($this->name, 'form_error_input_multiple_empty');
							$valid = false;
						}
					} else {
						foreach ($value as $val) {
							if (!is_array($val)) {
								if ((is_object($val) && !isset($this->options[$val->id])) || !@isset($this->options[$val])) {
									$found = false;
									$val_id = is_object($val) ? $val->id:$val;

									foreach ($this->options as $opt_id => $opt_val) {
										if ($opt_val instanceof \System\Model\Database && $opt_val->id == $val_id) {
											$found = true;
										}
									}

									if ($found) {
										$valid = true;
									} else {
										$this->form()->report_error($this->name, 'form_error_input_out_of_options');
										$valid = false;
									}
								}
							} else {
								$valid = false;
								throw new \System\Error\Form(sprintf('Value for %s must not be array!', $this->name), sprintf('"%s"" was given', var_export($value, true)));
							}
						}
					}
				}
			}

			return $valid;
		}


		public function get_element_name()
		{
			return in_array($this->type, array('submit', 'button', 'reset')) ? 'button':'input';
		}


		public function to_object()
		{
			$data = parent::to_object();
			unset($data['id']);
			unset($data['kind']);
			unset($data['options']);

			return $data;
		}
	}
}
