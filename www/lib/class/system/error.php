<?

namespace System
{
	class Error extends \Exception
	{
		protected $explanation = array();
		protected $backtrace = array();
		const HTTP_STATUS = 500;


		function __construct()
		{
			$this->explanation = func_get_args();
			$this->backtrace = debug_backtrace();
		}


		public function get_explanation()
		{
			return $this->explanation;
		}


		public function get_backtrace()
		{
			return $this->backtrace;
		}


		public function get_name()
		{
			return str_replace('system/error/', '', \System\Loader::get_class_file_name(get_class($this)));
		}


		public function get_http_status()
		{
			return \System\Http::get_header($this::HTTP_STATUS);
		}


		public static function from_exception(\Exception $e)
		{
			return new self($e->getMessage());
		}
	}
}
