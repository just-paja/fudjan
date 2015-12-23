<?php

namespace System\Form\Input
{
	class Number extends \System\Form\Input
	{
		public function is_valid()
		{
			$valid = parent::is_valid();
			$value = $this->val();

			if ($value) {
				$valid = $valid && preg_match("/^-?(?:\d+|\d*(\.\d+)?)$/", $value);
			}

			return $valid;
		}
	}
}
