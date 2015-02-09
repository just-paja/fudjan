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
			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error\Config $e) {
				$debug = false;
			}

			while(ob_get_level() > 0) {
				ob_end_clean();
			}

			if (!isset(self::$log_files[$type]) || !is_resource(self::$log_files[$type])) {
				try {
					\System\Directory::check(BASE_DIR.self::DIR_LOGS);
					self::$log_files[$type] = @fopen(BASE_DIR.self::DIR_LOGS.'/'.$type.'.log', 'a+');
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

				if (self::on_cli()) {
					$report .= "Run from console".NL;
				} else {
					$report .= "Request: ".$_SERVER['REQUEST_METHOD'].' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_URI']."'".NL;
				}

				if ($msg instanceof \System\Error) {
					$exp = $msg->get_explanation();

					foreach ($exp as $line) {
						$report .= "> ".$line.NL;
					}

					$msg = $msg->get_backtrace();
				}

				$report .= self::get_msg_text(array($msg));
				$report .= NL;

				if (!$debug && $type == 'error') {
					try {
						$rcpt = \System\Settings::get('dev', 'mailing', 'errors');
					} catch(\System\Error\Config $e) {
						$rcpt = array();
					}

					\System\Offcom\Mail::create('[Fudjan] Server error', $report, $rcpt)->send();
				}

				fwrite(self::$log_files[$type], $report);
			}
		}


		/**
		 * Add message info to report
		 *
		 * @param mixed  $msg
		 * @param string $report
		 */
		private static function get_msg_text($msg)
		{
			$report = '';

			foreach ($msg as $line) {
				if ($line) {
					if (is_array($line)) {
						$row = array('', '');

						if (isset($line['file'])) {
							$row[0] = $line['file'];

							if (isset($line['line'])) {
								$row[0] .= ":".$line['line'];
							}
						}

						if (isset($line['class'])) {
							$row[1] .= '  '.$line['class'].$line['type'];
						}

						if (isset($line['function'])) {
							if (!isset($line['class'])) {
								$row[1] .= '  ';
							}

							$row[1] .= $line['function'];
						}

						if ($row) {
							$report .= implode(NL, array_filter($row)).NL;
						}

						if (isset($line[0])) {
							$report .= self::get_msg_text($line, $report);
						}
					} else {
						$report .= "> ".$line.NL;
					}
				}
			}

			return $report;
		}


		/** General exception handler - Catches exception and displays error
		 * @param \Exception $e
		 * @param bool $ignore_next Don't inwoke another call of catch_exception from within
		 */
		public static function catch_exception(\Exception $e, $ignore_next = false)
		{
			// Kill output buffer
			while (ob_get_level() > 0) {
				ob_end_clean();
			}

			// Get error display definition
			try {
				$errors = \System\Settings::get('output', 'errors');
				$cfg_ok = true;
			} catch(\System\Error\Config $exc) {
				$errors = array();
				$cfg_ok = false;
			}

			// See if debug is on
			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error\Config $exc) {
				$debug = true;
			}

			// Convert to standart class error if necessary
			if (!($e instanceof \System\Error)) {
				$e = \System\Error::from_exception($e);
			}

			// Try saving error into logfile
			try {
				self::report('error', $e);
			} catch (\Exception $err) {
				$e = $err;
			}

			if ($e instanceof \System\Error\Request && $e::REDIRECTABLE && $e->location) {
				header('Location: '. $e->location);
				exit(0);
			} else {
				// Find error display template
				if (array_key_exists($e->get_name(), $errors)) {
					$error_page = $errors[$e->get_name()];
				} else {
					$error_page = array(
						"title"    => 'Error occurred!',
						"layout"   => array('system/layout/error'),
						"partial"  => 'system/error/bug',
					);
				}

				// Setup output format for error page
				$error_page['format'] = 'html';
				$error_page['render_with'] = 'basic';

				if (!isset($error_page['partial'])) {
					$error_page['partial'] = array('system/error/bug');
				}

				if (!is_array($error_page['partial'])) {
					$error_page['partial'] = array($error_page['partial']);
				}

				if ($debug && !in_array('system/error/bug', $error_page['partial'])) {
					$error_page['partial'][] = 'system/error/bug';
				}

				$request = \System\Http\Request::from_hit();
				$response = $request->create_response($error_page);

				self::load_locales_safe($request, $response);

				try {
					$response->renderer()->format = 'html';

					if (self::on_cli()) {
						$response->renderer()->format = 'txt';
					} else {
						$response->status($e->get_http_status());
					}

					foreach ($error_page['partial'] as $partial) {
						$response->renderer()->partial($partial, array("desc" => $e));
					}
				} catch (\Exception $exc) {
					header('HTTP/1.1 500 Internal Server Error');
					echo "Fatal error when rendering exception details";
					v($exc);
					exit(1);
				}

				$response
					->render()
					->send_headers()
					->send_content();
			}

			exit(1);
		}


		/**
		 * Try to load at least some locales for error report if possible.
		 *
		 * @param \System\Http\Request $rq
		 * @param \System\Http\Response $res
		 * @return void
		 */
		protected static function load_locales_safe(\System\Http\Request $rq, \System\Http\Response $res)
		{
			try {
				$res->locales->load_messages();
			} catch (\System\Error\Locales $e) {
				try {
					$def = \System\Settings::get('locales', 'default_lang');
				} catch (\System\Error\Config $e) {
					try {
						$list = \System\Settings::get('locales', 'allowed');
					} catch (\System\Error\Config $e) {
						echo "Fatal error when picking locales";
						v($exc);
						exit(1);
					}

					if (is_array($list) && count($list) > 0) {
						$def = $list[0];
					} else {
						echo "Fatal error when picking locales. Settings key locales.allowed must be array.";
						v($exc);
						exit(1);
					}
				}

				$rq->lang = $def;
			}

			try {
				$res->locales
					->set_locale($rq->lang)
					->load_messages();
			} catch (\System\Error\Locales $e) {
				echo "Fatal error when picking locales. Default language is not allowed. Config path locales.default_lang.";
				exit(1);
			}
		}


		public static function catch_error($number, $string, $file = null, $line = null, $context = array())
		{
			if (error_reporting()) {
				self::catch_exception(new \System\Error\Code($string.' in "'.$file.':'.$line.'"'));
			}
		}


		public static function catch_fatal_error()
		{
			$err = error_get_last();

			if (!is_null($err)) {
				if (any($err['message'])) {
					if (strpos($err['message'], 'var_export does not handle circular') !== false) {
						return;
					}
				}

				self::catch_error(def($err['number']), def($err['message']), def($err['file']), def($err['line']));
			}
		}


		public static function on_cli()
		{
			return php_sapi_name() == 'cli';
		}


		/** Introduce pwf name and version
		 * @return string
		 */
		public static function introduce()
		{
			return 'fudjan';
		}


		public static function init()
		{
			set_exception_handler(array("System\Status", "catch_exception"));
			set_error_handler(array("System\Status", "catch_error"));
			register_shutdown_function(array("System\Status", "catch_fatal_error"));

			ini_set('log_errors',     true);
			ini_set('display_errors', false);
			ini_set('html_errors',    false);
		}
	}
}
