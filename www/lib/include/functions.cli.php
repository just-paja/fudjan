<?

/* Output to STDOUT
 * @param string $str    String to print
 * @param bool   $break  Print carriage return
 * @return void;
 */
function out($str = '', $break = true, $return = false) {
	$str = $str.($break ? CR:'');
	if ($return) return $str; else echo $str;
}


/* Output to STDOUT if verbose
 * @param string $str    String to print
 * @param bool   $break  Print carriage return
 * @return void;
 */
function vout($str = '', $break = true, $return = false) {
	if (CLIOptions::get('verbose')) {
		$str = out($str, $break, $return);
		if ($return) return $str; else echo $str;
	}
}


/* Print heading separator to STDOUT
 * @return void
 */
function sep($return = false) {
	$str = '';
	for ($i = 0; $i <= CLIOptions::get_con_width(); $i++) {
		$str .= '-';
	}
	$str .= CR;
	
	if ($return) return $str; else echo $str;
}


/** Print a set of paired keys and values
 * @return void
 */
function out_flist(array $list, $semicolon = true, $margin = 0, $return = false, $key_color = 'normal', $purge = false) {
	$str = '';
	$maxlen = 0;

	foreach ($list as $key => $value) {
		(strlen($key) > $maxlen) && $maxlen = strlen($key);
	}

	$maxlen ++;

	foreach ($list as $key => $value) {
		if (!$purge || ($purge && $value != '' && $value !== null)) {
			$output = $key.($semicolon ? ": ":" ");
			$output = class_exists("System\Cli") ? \System\Cli::term_color($output, $key_color):$output;
			$str .= out(str_repeat(" ", $margin).$output, false, true);

			for ($padding = strlen($key); $padding < $maxlen; $padding++) {
				$str .= out(" ", false, true);
			}

			$str .= out($value, true, true);
		}
	}

	if ($return) return $str; else echo $str;
}


/* Kill script with return code
 * @returns int Return code
 */
function give_up($str, $code = 1) {
	echo $str.CR;
	exit($code);
}


/* Read from user input
 * @param string Info for user input
 * @param bool   Are we inputing password?
 * @returns string
 */
function read($str, $pass = false) {
	out($str, false);
	$val = trim(shell_exec("read ".($pass ? '-s ':'')."str; echo \$str"));
	$pass && print("\n");
	return $val;
}


function is_yes($str) {
	return in_array(strtolower($str), array("yes", "y", "1", "a"));
}


function show_progress_cli($done, $total, $size=30, $done_msg = "Finished in %d seconds", $static_msg = '') {

	static $start_time;

	// if we go over our bound, just ignore it
	if ($done > $total) return;
	if (empty($start_time)) $start_time = time();

	$now = time();
	$perc=(double)($done/$total);
	$bar=floor($perc*$size);

	$status_bar="\r  [";
	$status_bar.=str_repeat("=", $bar);

	if ($bar < $size) {
		$status_bar .= ">";
		$status_bar .= str_repeat(" ", $size - $bar);
	} else {
		$status_bar.="=";
	}

	$disp = number_format($perc*100, 0);
	$status_bar .= "] $disp%";

	$rate = $done > 0 ? ($now-$start_time)/$done:0;
	$left = $total - $done;
	$eta = round($rate * $left, 2);

	$elapsed = $now - $start_time;

	$status_bar.= ": ".$static_msg;
	//$status_bar.= " remaining: ".number_format($eta)."s";

	echo $status_bar;
	flush();

	// when done, send a newline
	if($done == $total) {
		out();
		if (any($done_msg)) {
			out();
			out(sprintf($done_msg, number_format($elapsed)));
		}
	}

}
