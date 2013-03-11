<?

out_flist(array(
	'Page path'   => System\Page::get_path(),
	'SQL Queries' => System\Query::count_all(),
	'Exec time'   => round(System\Flow::get_exec_time(), 6).'s',
));
