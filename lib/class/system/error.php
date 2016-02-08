<?php

namespace System
{
	class Error extends \Exception
	{
		protected $explanation = array();
		protected $backtrace = array();

		const HTTP_STATUS = 500;
		const REDIRECTABLE = false;


		function __construct()
		{
			$this->explanation = func_get_args();

			if (array_key_exists(0, $this->explanation)) {
				$this->message = $this->explanation[0];
			}
		}


		public function get_explanation()
		{
			return $this->explanation;
		}


		public function set_explanation(array $exp)
		{
			$this->explanation = $exp;
		}


		public function get_name()
		{
			return str_replace('system/error/', '', \System\Loader::get_class_file_name(get_class($this)));
		}


		public function get_http_status()
		{
			return $this::HTTP_STATUS;
		}


		public static function from_exception(\Exception $e)
		{
			if ($e instanceof \Exception && !($e instanceof \System\Error)) {
				$exc = new self($e->getMessage());
				$exc->backtrace = $e->getTrace();
				return $exc;
			}

			return $e;
		}


    public function setMessage($msg)
    {
      $this->message = $msg;
    }
	}
}
