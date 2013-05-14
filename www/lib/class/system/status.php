<?

namespace System
{
	abstract class Status
	{
		const DIR_LOGS = '/var/log';

		private static $log_files = array();


		/** Write error into log file
		 * @param string $type
		 * @param string $msg
		 */
		public static function report($type, $msg)
		{
			if (!isset(self::$log_files[$type]) || !is_resource(self::$log_files[$type])) {
				try {
					\System\Directory::check(ROOT.self::DIR_LOGS);
					self::$log_files[$type] = @fopen(ROOT.self::DIR_LOGS.'/'.$type.'.log', 'a+');
				} catch(\System\Error $e) {
					self::error($e, false);
				}
			}

			if (is_resource(self::$log_files[$type])) {
				try {
					$report = @date('[Y-m-d H:i:s]');
				} catch(\Exception $e) {
					$report = time();
				}

				!self::on_cli() && $report .= ' '.$_SERVER['SERVER_NAME'].NL;
				self::append_msg_info($msg, $report);

				if (self::on_cli()) {
					$report .= "> Run from console".NL;
				} else {
					$report .= "> Request: ".$_SERVER['REQUEST_METHOD'].' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_URI']."'".NL;
				}
				$report .= NL;
				fwrite(self::$log_files[$type], $report);
			}
		}


		/** Add message info to report
		 * @param mixed  $msg
		 * @param string $report
		 */
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


		/** General exception handler - Catches exception and displays error
		 * @param \Exception $e
		 * @param bool $ignore_next Don't inwoke another call of catch_exception from within
		 */
		public static function catch_exception(\Exception $e, $ignore_next = false)
		{
			while (ob_get_level() > 0) {
				ob_end_clean();
			}

			try {
				$errors = cfg('output', 'errors');
				$cfg_ok = true;
			} catch(\System\Error $exc) {
				$errors = array();
				$cfg_ok = false;
			}

			if (!($e instanceof \System\Error)) {
				$e = \System\Error::from_exception($e);
			}

			if (array_key_exists($e->get_name(), $errors)) {
				$error_page = $errors[$e->get_name()];
			} else {
				$error_page = array(
					"title"    => 'Error occurred!',
					"layout"   => array('pwf/errors/layout'),
					"partial"  => 'system/error/bug',
				);
			}

			try {
				$request = \System\Http\Request::from_hit();
				$page = new \System\Page($error_page);
				$response = \System\Http\Response::from_page($request, $page);

				if (!self::on_cli()) {
					$response->status($e->get_http_status());
				}

				if (!isset($error_page['partial'])) {
					$error_page['partial'] = 'system/error/bug';
				}

				$response->partial($error_page['partial'], array("desc" => $e));

				$response->render()->send_headers()->display();
				self::report('error', $e);

			} catch (\Exception $exc) {
				echo "Fatal error";
				v($exc);
				exit(1);
			}

			exit(1);
		}


		public static function catch_error($number, $string, $file = null, $line = null, $context = array())
		{
			if (error_reporting()) {
				self::catch_exception(new \System\Error\Code($string.' in "'.$file.':'.$line.'"'));
			}
		}


		public static function on_cli()
		{
			return php_sapi_name() == 'cli';
		}
	}
}
