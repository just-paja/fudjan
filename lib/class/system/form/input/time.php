<?php

namespace System\Form\Input
{
	class Time extends \System\Form\Input
	{
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
