#!/usr/bin/php
<?

define('ROOT', realpath(__DIR__));

require_once ROOT.'/www/lib/include/constants.cli.php';
require_once ROOT.'/www/lib/include/functions.php';
require_once ROOT.'/www/lib/include/functions.cli.php';
require_once ROOT.'/www/lib/class/system/cli.php';
require_once ROOT.'/lib/class/compiler.php';
require_once ROOT.'/lib/class/compiler/process.php';
require_once ROOT.'/lib/class/pwf.php';


out('Preparing workspace ..');

System\Cli::init();
$branch = Pwf::get_branch(ROOT);
$meta = Pwf::read_meta_info(ROOT, $branch);
Compiler::provide_meta($meta);
Compiler::prepare_workspace();



// Prepare data and meta
out('Compiling package .. ');

Compiler::run('prepare');
Compiler::process('compile', 'Preparing files', array(), function($make, $data) {
	$pkg = Compiler::get('package');
	$path = Compiler::get('path');
	$info = Compiler::get('meta');

	$make->progress(0, 100);
	exec('cd "'.$pkg['dir-src'].'"; git archive --format tar master > '.$path['file-tar']);

	$make->progress(50, 100);
	exec('
		cd '.$path['dir-data'].';
		tar -xf '.$path['file-tar'].';
		cd '.$pkg['dir-src'].';
		git log > '.$path['dir-meta'].'/changelog;
		cd '.$path['dir-data'].';
		echo '.$info['project-name'].   ' > '.$path['file-version'].';
		echo '.$info['project-desc'].   ' >> '.$path['file-version'].';
		echo '.$info['package-version'].' >> '.$path['file-version'].';
		echo '.$info['package-category'].'/'.$info['package-name'].' >> '.$path['file-version'].';
		rm '.$path['file-tar'].';
	');

	$make->progress(100, 100);
	return true;
});



// Create files checksum
Compiler::process('checksum', 'Calculating package checksums', array(), function($make, $data) {
	$pkg = Compiler::get('package');
	$path = Compiler::get('path');
	$make->progress(0, 100);

	Compiler::$files = array_merge(Compiler::$files, array_filter(explode("\n", shell_exec('cd '.$pkg['dir-src'].'; git ls-files'))));
	$total = count(Compiler::$files) - count(Compiler::$banned_files);

	foreach (Compiler::$files as $key=>$file) {
		if (!in_array($file, Compiler::$banned_files)) {
			exec('cd '.$path['dir-data'].'; md5sum '.$file.' >> '.$path['file-checksum']);
		}
		$make->progress($key+1, $total);
	}

	return file_exists($path['file-checksum']);
});



// Pack it all together
Compiler::process('archive', 'Creating package archive', array(), function($make, $data) {
	$path = Compiler::get('path');
	$make->progress(0, 100);
	exec('cd '.$path['dir-temp'].'; tar -c `ls` > '.$path['output-temp']);
	$make->progress(50, 100);
	exec('cd '.$path['dir-temp'].'; bzip2 '.$path['output-temp']);
	$make->progress(100, 100);

	return file_exists($path['output']);
});



// Run postprocess actions if any
Compiler::run('postprocess');



// Clean temp files
Compiler::process('clean', 'Cleaning workspace', array(), function($make, $data) {
	$make->progress(0, 100);
	$path = Compiler::get('path');
	$files = array();
	$dirs = array();

	Compiler::read_dir($path['dir-temp'], $files, $dirs);
	$total = count($files) + count($dirs);
	$x = 1;

	foreach ($files as $f) {
		unlink($f);
		$make->progress($x++, $total);
	}

	foreach ($dirs as $f) {
		rmdir($f);
		$make->progress($x++, $total);
	}

	rmdir($path['dir-temp']);
	return true;
});



// Write response
$path = Compiler::get('path');
Compiler::message('Package was created in "'.$path['output'].'"');
out();

foreach (Compiler::get('messages') as $msg) {
	out($msg);
}
