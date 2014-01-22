<?

namespace System\Form\Input
{
	class Checkbox extends \System\Form\Input
	{
		protected static $resources = array(
			"scripts" => array('bower/pwf-form/lib/input/checkbox'),
		);


		public function construct()
		{
			parent::construct();

			if ($this->options) {
				$this->multiple = true;
			}
		}


		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				$valid = is_array($value);

				if ($valid) {
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
				}
			}

			return $valid;
		}
	}
}
