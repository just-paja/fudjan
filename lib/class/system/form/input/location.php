<?

namespace System\Form\Input
{
	class Location extends \System\Form\Input
	{
		protected static $resources = array(
			"scripts" => array(
				'bower/pwf-input-gps/lib/gps',
				'bower/pwf-input-location/lib/location'
			),
		);


		public function val_get()
		{
			$val = $this->form()->input_value($this->name);

			if ($val) {
				if (gettype($val) == 'string') {
					$val = \System\Json::decode($val);
				}

				if (!($val instanceof \System\Location)) {
					$val = new \System\Location($val);
				}
			}

			return $val;
		}
	}
}
