<?



/** Show progress bar on cli
 * @param int    $done       Count of items, that are done
 * @param int    $total      Total count of items
 * @param int    $size       Width of progress bar in chars
 * @param string $done_msg   Message that will be printed on finish
 * @param string $static_msg Message that will be printed during the process
 * @return void
 */
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
