<?

namespace System
{
	abstract class Status
	{

		const DIR_LOGS = '/var/log';

		private static $log = array();
		private static $log_files = array();

		public static $save_referer = true;

		public static function unlog()
		{
			return self::$log;
		}


		public static function log($where, array $what, $status = null, $fatal = false)
		{
			if (!isset(self::$log[$where])) self::$log[$where] = array();
			$e = &self::$log[$where][];
			$e = array_merge($what, array("status" => $status));
		}


		public static function fatal_error($desc)
		{
			self::report('fatal', $desc);
			//!Settings::get('dev', 'debug') && ob_end_clean();
			$format = Output::get_format() ? Output::get_format():'html';

			if (file_exists($f = ROOT."/lib/template/errors/bug.".$format.".php")) {
				require $f;
			} else require ROOT."/lib/template/errors/bug.html.php";
			exit;
		}


		public static function recoverable_error($id, $desc = null)
		{
			//!Settings::get('dev', 'debug') && ob_end_clean();
			$format = Output::get_format() ? Output::get_format():'html';
			header("HTTP/1.0 ".$id);
			include ROOT."/lib/template/errors/".$id.".".$format.".php";
			exit;
		}


		public static function format_errors($desc, array &$errors = array())
		{
			if (is_array($desc)) {
				foreach ($desc as $d) {
					Status::format_errors($d, $errors);
				}
			} elseif (is_object($desc)) {
				if ($desc instanceof \System\Error) {
					foreach ($desc->get_explanation() as $msg) {
						$errors[] = $msg;
					}

					foreach ($desc->get_backtrace() as $msg) {
						$errors[] = implode(':', $msg);
					}
				} else {
					$errors[] = var_export($desc);
				}
			} else {
				$errors[] = $desc;
			}
			return $errors;
		}


		public static function report($type, $msg)
		{
			if (!isset(self::$log_files[$type]) || !is_resource(self::$log_files[$type])) {
				\System\Directory::check(ROOT.self::DIR_LOGS);
				self::$log_files[$type] = fopen(ROOT.self::DIR_LOGS.'/'.$type.'.log', 'a+');
			}

			$report = @date('[Y-m-d H:i:s]');
			php_sapi_name() != 'cli' && $report .= ' '.$_SERVER['SERVER_NAME'].NL;
			self::append_msg_info($msg, $report);


			if (php_sapi_name() == 'cli') {
				$report .= "> Run from console".NL;
			} else {
				$report .= "> Request: ".$_SERVER['REQUEST_METHOD'].' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_URI']."'".NL;
			}
			$report .= NL;
			fwrite(self::$log_files[$type], $report);
		}


		private static function append_msg_info($msg, &$report)
		{
			foreach ((array) $msg as $line) {
				if ($line) {
					if (is_array($line)) {
						if (isset($line[0])) {
							self::append_msg_info($line, $report);
						} else {
							$report .= "> ".json_encode($line).NL;
						}
					} else {
						$report .= "> ".$line.NL;
					}
				}
			}
		}


		public static function catch_exception(\Exception $e)
		{
			$sql = method_exists($e, "getSql") ? $e->getSql():'';
			self::fatal_error($e);
		}
	}
}
