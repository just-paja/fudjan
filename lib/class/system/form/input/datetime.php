<?php

namespace System\Form\Input
{
	class Datetime extends \System\Form\Input
	{
		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
			}

			return $valid;
		}


		public function val_get()
		{
			$val = $this->form()->input_value($this->name);

			if ($val && gettype($val) == 'string') {
				$val = new \DateTime($val);
			}

			return $val;
		}
	}
}
