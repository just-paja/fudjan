<?

namespace System\Form\Input
{
	class Select extends \System\Form\Input
	{
		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				if ($this->multiple) {
					if (is_array($this->options) && is_array($value)) {
						foreach ($value as $item) {
							foreach ($this->options as $opt) {
								if ($opt['value'] == $value) {
									$valid = true;
									break;
								} else {
									$valid = false;
								}
							}

							if (!$valid) {
								break;
							}
						}
					} else {
						$valid = false;
					}
				} else {
					foreach ($this->options as $opt) {
						if ($opt['value'] == $value) {
							$valid = true;
							break;
						} else {
							$valid = false;
						}
					}
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
