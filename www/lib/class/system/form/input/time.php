<?

namespace System\Form\Input
{
	class Time extends \System\Form\Input
	{
		protected static $resources = array(
			"scripts" => array(
				'bower/moment/moment',
				'bower/pwf-moment-compat/lib/moment-compat',
				'bower/pwf-form/lib/input/time'
			),
		);


		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				$valid = preg_match("/^[0-9]{2}(:[0-9]{2}(:[0-9]{2})?)?$/", $value);
			}

			return $valid;
		}
	}
}
