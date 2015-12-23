<?php

namespace System\Error
{
	class Request extends \System\Error
	{
		const HTTP_STATUS = 400;

		public $location = null;
		public $alternatives = array();
	}
}
