<?

namespace System
{
	abstract class Status
	{
		const DIR_LOGS = '/var/log';

		private static $log_files = array();

		public static $save_referer = true;

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
				try {
					\System\Directory::check(ROOT.self::DIR_LOGS);
					self::$log_files[$type] = @fopen(ROOT.self::DIR_LOGS.'/'.$type.'.log', 'a+');
				} catch(\System\Error $e) {
					self::error($e, false);
				}
			}

			if (is_resource(self::$log_files[$type])) {
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
			$errors = cfg('output', 'errors');

			if (array_key_exists($e->get_name(), $errors)) {
				$error_page = $errors[$e->get_name()];
			} else {
				$error_page = array(
					"title"    => 'l_error',
					"template" => array('pwf/errors/layout', 'pwf/errors/bug'),
				);
			}

			header($e->get_http_status());

			try {
				$page = new \System\Page($error_page);
				\System\Output::set_opts(array(
					"format"   => cfg("output", 'format_default'),
					"title"    => $page->title,
					"template" => $page->template,
					"page"     => $page->seoname,
				));

				\System\Output::out();
				self::report('error', $e);

			} catch (\Exception $e) { self::catch_exception($e); }
		}
	}
}
