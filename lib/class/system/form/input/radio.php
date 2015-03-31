<?

namespace System\Form\Input
{
	class Radio extends \System\Form\Input\Options
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
			return parent::is_valid() && $this->is_in_options();
		}
	}
}
