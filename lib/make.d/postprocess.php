<?

$path = Compiler::get('path');
$version = Compiler::get('version');
$info = Compiler::get('info');
$version_dir = $path['dir-data'].'/etc/current/core/pwf';
$version_file = $path['dir-data'].'/etc/current/core/pwf/version';
$path['output-uf'] = 'pwf-ready-'.$version;

exec('
	mkdir -p '.$version_dir.';
	echo "'.$info['package-name'].'" > '.$version_file.';
	echo "'.$info['project-name'].'" >> '.$version_file.';
	echo "'.$version.'" >> '.$version_file.';
	cd '.$path['dir-temp'].'/temp; cp -R data '.$path['dir-temp'].'/'.$path['output-uf'].';
	cp '.$path['dir-meta'].'/* "'.$path['dir-temp'].'/'.$path['output-uf'].'/etc/current/core/pwf/";
');

// Pack it all together
Compiler::process('postprocess.user-friendly-package', 'Creating user ready package', $path, function($make, $path) {
	$pkg = Compiler::get('package');
	$p = $path['output-dir'].'/'.$path['output-uf'].($pkg['branch'] === 'master' ? '':'.'.$pkg['branch']).'.tar.bz2';

	$make->progress(0, 100);
	exec('cd '.$path['dir-temp'].'; tar -c '.$path['output-uf'].' > '.$path['output-uf'].'.tar');
	$make->progress(33, 100);
	exec('cd '.$path['dir-temp'].'; bzip2 '.$path['output-uf'].'.tar');
	$make->progress(66, 100);
	exec('cd '.$path['dir-temp'].'; cp '.$path['output-uf'].'.tar.bz2 '.$p);
	$make->progress(100, 100);

	Compiler::message('User ready package was created in "'.$p.'"');
	return file_exists($p);
});
