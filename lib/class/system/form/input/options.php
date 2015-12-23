<?php

namespace System\Form\Input
{
	abstract class Options extends \System\Form\Input
	{
		public function is_in_options()
		{
			$value = $this->val();
			$valid = true;

			if (is_array($this->options)) {
				$opts  = $this->options;
				$valid = false;

				foreach ($opts as $opt) {
					if (is_array($opt)) {
						if (array_key_exists('value', $opt) && $opt['value'] == $value) {
							$valid = true;
							break;
						}
					} else if ($opt == $value) {
						$valid = true;
						break;
					}
				}

				if (!$valid) {
					$this->form()->report_error($this->name, array(
						'message' => 'out-of-options',
						'value'   => $value,
						'options' => $opts,
					));
				}

			}

			return $valid;
		}
	}
}
