<?

namespace System\Form\Input
{
	class Month extends \System\Form\Input
	{
		protected static $resources = array(
			"scripts" => array(
				'bower/moment/moment',
				'bower/pwf-moment-compat/lib/moment-compat',
				'bower/pwf-form/lib/input/month'
			),
		);


		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				$valid = preg_match("/^[0-9]+\-[0-1][0-9]$/", $value);
			}

			return $valid;
		}
	}
}
