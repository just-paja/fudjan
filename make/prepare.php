<?

$path = PackageInfo::get('path');

exec('
	mkdir '.$path['dir-data'].'/share;
	mkdir '.$path['dir-data'].'/var;
	chmod 777 '.$path['dir-data'].'/var;
');
