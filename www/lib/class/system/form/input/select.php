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
					if (is_array($this->options)) {
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
	}
}
