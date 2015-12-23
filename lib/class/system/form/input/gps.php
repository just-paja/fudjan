<?php

namespace System\Form\Input
{
	class Gps extends \System\Form\Input
	{
		public function val_get()
		{
			$val = $this->form()->input_value($this->name);

			if ($val) {
				if (gettype($val) == 'string') {
					$val = \System\Json::decode($val);
				}

				if (!($val instanceof \System\Gps)) {
					$val = new \System\Gps($val);
				}
			}

			return $val;
		}
	}
}
