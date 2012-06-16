<?

if (!defined("H_BUG_REPORT_TXT")) {
	define("H_BUG_REPORT_TXT", true);

	function yacms_show_backtrace_txt($trace = null, $padd = 0)
	{
		if (is_null($trace)) {
			$trace = debug_backtrace();
		}

		if (any($trace)) {
				foreach ($trace as $t) {
					if (is_array($t)) {
						if (isset($t['file'])) out(str_repeat(" ", $padd+2).$t['file'].':'.$t['line']);
						isset($t['args']) && is_array($t['args']) && yacms_show_backtrace_txt($t['args'], $padd +2);
					} elseif ($t instanceof Exception) {
						out(str_repeat(" ", $padd+2).get_class($t).': '.$t->getMessage());
					} else {
						out(str_repeat(" ", $padd+2).var_export($t, true));
					}
				}
		}
	}
}

out();
sep();
out("System error!");
out();
$errors = Core\System\Status::format_errors($desc);
foreach ($errors as $e) {
	out("  ".$e);
}

out();

yacms_show_backtrace_txt();
sep();
