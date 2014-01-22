<?

namespace System\Form\Input
{
	class Date extends \System\Form\Input
	{
		protected static $resources = array(
			"styles" => array('bower/pwf-form/styles/date'),
			"scripts" => array(
				'bower/moment/moment',
				'bower/pwf-moment-compat/lib/moment-compat',
				'bower/pwf-form/lib/input/date'
			),
		);

		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				$valid = preg_match("/^[0-9]+\-[0-1][0-9]\-[0-3][0-9]$/", $value);
			}

			return $valid;
		}
	}
}
