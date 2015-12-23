<?php

namespace System\Form\Input
{
	class Date extends \System\Form\Input
	{
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
