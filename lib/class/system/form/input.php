<?

namespace System\Form
{
	class Input extends \System\Form\Element
	{
		protected static $attrs = array(
			"name"         => array("type" => "varchar"),
			"type"         => array("type" => "varchar"),
			"label"        => array("type" => "varchar"),
			"desc"         => array("type" => "varchar"),
			"placeholder"  => array("type" => 'varchar'),
			"maxlength"    => array("type" => 'int'),
			"minlength"    => array("type" => 'int'),
			"step"         => array("type" => 'float'),
			"min"          => array("type" => 'int'),
			"max"          => array("type" => 'int'),
			"required"     => array("type" => 'bool'),
			"options"      => array("type" => 'array'),
			"multiple"     => array("type" => 'bool'),

			// Widget specific
			'ident' => array("type" => "varchar"),
		);


		private static $buttons = array('submit', 'button', 'reset');


		protected function construct()
		{
			!$this->id && $this->id = 'field_'.$this->name;

			if (!$this->name) {
				throw new \System\Error\Form('You must enter input name!', $this->type);
			}
		}


		public function is_valid()
		{
			$valid = true;
			$value = $this->val();

			if ($this->required && !$value) {
				$this->form()->report_error($this->name, 'form_error_input_empty');
				$valid = false;
			}

			return $valid;
		}


		public function val($value=null)
		{
			if (is_null($value)) {
				$val = method_exists($this, 'val_get') ? $this->val_get():$this->form()->input_value($this->name);

				if (!$val) {
					$val = null;
				}

				return $val;
			} else {
				if (method_exists($this, 'val_set')) {
					$this->val_set($value);
				} else {
					$this->form()->use_value($this->name, $value);
				}
			}

			return $this;
		}


		public function to_object()
		{
			$data = parent::to_object();

			if (in_array($this->type, self::$buttons)) {
				$data['element'] = 'button';
			} else {
				$data['element'] = 'input';
			}

			if (array_key_exists('options', $data)) {
				$opts = array();

				foreach ($data['options'] as $key=>$value) {
					$name = $value;

					if (is_object($name) && $name instanceof \System\Model\Attr) {
						$name = $name->name;
					}

					if (is_array($value) && array_key_exists('name', $value) && array_key_exists('name', $value)) {
						$opts[] = $value;
					} else {
						$opts[] = array(
							"name"  => $name,
							"value" => $key
						);
					}
				}


				$data['options'] = $opts;
			}

			return $data;
		}
	}
}
