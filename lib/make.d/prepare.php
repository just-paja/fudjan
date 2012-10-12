<?

$path = Compiler::get('path');

exec('
	umask 002 '.$path['dir-data'].';
	mkdir '.$path['dir-data'].'/share;
	mkdir '.$path['dir-data'].'/var;
	chmod 777 '.$path['dir-data'].'/var;
');

$result =
	is_dir($path['dir-data'].'/share') &&
	is_dir($path['dir-data'].'/var');
