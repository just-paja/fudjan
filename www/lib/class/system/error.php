<?

namespace System
{
	class Error extends \Exception
	{
		protected $data = array();
		protected $backtrace = array();

		function __construct()
		{
			$d = func_get_args();

			if (isset($d[0]) && $d[0] === 'stack') {
				foreach ($d as $i=>$data) {
					if ($i != 0) {
						if (is_array($data)) {
							foreach ($data as $arg) {
								$this->data[] = $arg;
							}
						} else $this->data[] = $data;
					}
				}
			} else {
				$this->data = $d;
			}

			$backtrace = debug_backtrace();
			$len = count($backtrace);
			$i = $len >= 4 ? 3:0;

			for ($i; $i<$len; $i++) {
				$target = &$this->backtrace[];
				$target = array();

				isset($backtrace[$i]['file']) && $target['file'] = '    '.$backtrace[$i]['file'].(isset($backtrace[$i]['line']) ? ':'.$backtrace[$i]['line']:'');
				isset($backtrace[$i]['class']) && $target['class'] = '   '.$backtrace[$i]['class'];
				isset($backtrace[$i]['function']) && $target['function'] = $backtrace[$i]['function'];

				if ($i >= 6) {
					break;
				}
			}
		}


		public function get_explanation()
		{
			return $this->data;
		}


		public function get_backtrace()
		{
			return $this->backtrace;
		}


		public function get_name()
		{
			return str_replace('system/error/', '', \System\Loader::get_class_file_name(get_class($this)));
		}
	}
}
