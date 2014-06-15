<?

namespace System\Form
{
	class Input extends \System\Form\Element
	{
		protected static $attrs = array(
			"name"         => array("varchar"),
			"type"         => array("varchar"),
			"label"        => array("varchar"),
			"desc"         => array("varchar"),
			"placeholder"  => array('varchar'),
			"maxlen"       => array('int'),
			"step"         => array('float'),
			"min"          => array('int'),
			"max"          => array('int'),
			"required"     => array('bool'),
			"options"      => array('array'),
			"multiple"     => array('bool'),

			// Widget specific
			'ident' => array("varchar"),
		);

		protected static $resources = array(
			"scripts" => array(
				'bower/pwf-form/lib/input/hidden',
				'bower/pwf-form/lib/input/password',
				'bower/pwf-form/lib/input/textarea'
			)
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

			return $data;
		}


		public function get_resources($type)
		{
			return isset($this::$resources[$type]) ? $this::$resources[$type]:array();
		}
	}
}
