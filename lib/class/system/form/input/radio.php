<?

namespace System\Form\Input
{
	class Radio extends \System\Form\Input
	{
		public function val($value = null)
		{
			$val = parent::val($value);

			if ($val == 'false') {
				return false;
			}

			return $val;
		}


		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($valid && is_array($this->options)) {
				$opts = $this->options;

				$valid = array_key_exists($value, $opts);

				if (!$valid) {
					$this->form()->report_error($this->name, array(
						'message' => 'out-of-options',
						'value'   => $value,
						'options' => $opts,
					));
				}
			}

			return $valid;
		}
	}
}
