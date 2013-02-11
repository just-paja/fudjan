<?

echo "\n";
echo l('Fatal error has occurred')."\n";
echo '-------------------------'."\n";


if ($desc instanceof System\Error\Internal) {
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
