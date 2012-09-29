<?

$path = PackageInfo::get('path');
$version = PackageInfo::get('version');
$info = PackageInfo::get('info');
$version_dir = $path['dir-data'].'/etc/current/core/yawf';
$version_file = $path['dir-data'].'/etc/current/core/yawf/version';
$path_output = 'yawf-ready-'.$version;

exec('
	mkdir -p '.$version_dir.';
	echo "'.$info['package-name'].'" > '.$version_file.';
	echo "'.$info['project-name'].'" >> '.$version_file.';
	echo "'.$version.'" >> '.$version_file.';
	cd '.$path['dir-data'].'; cd ..; cp -R data '.$path_output.';
');


// Pack it all together
$msg = 'Creating user ready package';
show_progress_cli(0, 100, CONSOLE_WIDTH, '', $msg);
exec('cd '.$path['dir-temp'].'; tar -c '.$path_output.' > '.$path_output.'.tar');
show_progress_cli(33, 100, CONSOLE_WIDTH, '', $msg);
exec('cd '.$path['dir-temp'].'; bzip2 '.$path_output.'.tar');
show_progress_cli(66, 100, CONSOLE_WIDTH, '', $msg);
exec('cd '.$path['dir-temp'].'; cp '.$path_output.'.tar.bz2 ../');
show_progress_cli(100, 100, CONSOLE_WIDTH, '', $msg);
