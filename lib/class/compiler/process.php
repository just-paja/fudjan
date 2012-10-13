<?

namespace Compiler
{
	class Process
	{
		private $msg;
		private $data;
		private $lambda;
		private $console_width = 40;


		public function __construct($msg, array $data, \Closure $lambda)
		{
			$this->msg = $msg;
			$this->data = $data;
			$this->lambda = $lambda;
			$this->console_width = \System\Cli::get_width();
		}


		public function __get($what)
		{
			if (isset($this->what)) {
				return $this->$what;
			} else throw new Exception('Undefined property "'.$what.'".');
		}


		public function run()
		{
			$fn = $this->lambda;
			return $fn($this, $this->data);
		}


		public function progress($progress, $max)
		{
			show_progress_cli($progress, $max, $this->console_width-45, '', $this->msg);
		}
	}
}
