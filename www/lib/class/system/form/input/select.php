<?

namespace System\Form\Input
{
	class Select extends \System\Form\Input
	{
		protected static $resources = array(
			"scripts" => array('bower/pwf-form/lib/input/select'),
		);


		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				if ($this->multiple) {
					if (is_array($this->options) && is_array($value)) {
						foreach ($value as $item) {
							$valid = array_key_exists($item, $this->options);

							if (!$valid) {
								break;
							}
						}
					} else {
						$valid = false;
					}
				} else {
					$valid = array_key_exists($value, $this->options);
				}
			}

			return $valid;
		}


		public function val_get()
		{
			$value = $this->form()->input_value($this->name);

			if ($this->multiple && $value) {
				if (is_array($value)) {
					if (isset($value[0]) && !$value[0]) {
						$value = null;
					}
				}
			}

			return $value;
		}
	}
}
