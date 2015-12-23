<?php

namespace System\Router
{
	abstract class Arg
	{
		const PATTERN = '[^/]+';

		protected $val;

		public function get_val()
		{
			return $this->val;
		}
	}
}
