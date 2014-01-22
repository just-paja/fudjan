<?

out_flist(array(
	//~ 'Output format' => System\Output::get_format(true),
	//~ 'Page path'     => System\Page::get_path(),
	'SQL Queries'   => System\Database\Query::count_all(),
	//~ 'Exec time'     => round(System\Flow::get_exec_time(), 6).'s',
));
