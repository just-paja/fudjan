<?

$path = PackageInfo::get('path');

exec('
	umask 002 '.$path['dir-data'].';
	mkdir '.$path['dir-data'].'/share;
	mkdir '.$path['dir-data'].'/var;
	chmod 777 '.$path['dir-data'].'/var;
');
