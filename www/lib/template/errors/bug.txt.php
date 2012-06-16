<?

if (!defined("H_BUG_REPORT")) {
	define("H_BUG_REPORT", true);

	function yacms_show_backtrace($trace = null)
	{
		if (is_null($trace)) {
			$trace = debug_backtrace();
		}

		if (any($trace)) {
			echo '<ul>';
				foreach ($trace as $t) {
					echo '<li>';

					if (is_array($t)) {
						if (isset($t['file'])) echo $t['file'].':'.$t['line'];
						is_array($t['args']) && yacms_show_backtrace($t['args']);
					} elseif ($t instanceof Exception) {
						echo get_class($t).': '.$t->getMessage();
					} else {
						echo '<pre>';
						var_export($t);
						echo '</pre>';
					}

					echo '</li>';
				}
			echo '</ul>';
		}
	}
}

echo l('Fatal error has occurred')."\n";
echo '-------------------------'."\n";


if ($desc instanceof InternalException) {
	$msgs = $desc->get_explanation();
	$back = $desc->get_backtrace();

	foreach ($msgs as $row) {
		if (is_string($row)) {
			echo $row ."\n";
		} else {
			var_dump($row);
		}
	}
	
	foreach ($back as $trace) {
		echo "---\n";
		foreach ($trace as $key=>$msg) {
			echo $key.": ".$msg."\n";
		}
	}

} else {
	var_dump($desc);
}

//yacms_show_backtrace();
