<?

$path = Compiler::get('path');
define('WORKING_DIR_CLASSES', $path['dir-data'].'/lib/class/system');
define('WORKING_FILE_SYSTEM_CLASSES', $path['dir-data'].'/lib/include/system.php');

Compiler::$files[] = 'lib/include/system.php';



// Optimize system code
$msg = 'Compiling system code';
$result = Compiler::process('compile.classes', $msg, array(), function($make, $data) {
	$path = Compiler::get('path');
	$make->progress(0, 100);

	$code  = '<?';
	$used = $minified = array(
		WORKING_DIR_CLASSES.'/model/attr.php',
		WORKING_DIR_CLASSES.'/model/callback.php',
		WORKING_DIR_CLASSES.'/model/database.php',
	);
	$dirs = array();

	Compiler::read_dir(WORKING_DIR_CLASSES.'/model', $minified, $dirs, $used);
	Compiler::read_dir(WORKING_DIR_CLASSES, $minified, $dirs, $used);

	$minified[]  = 'namespace{';
	$minified[] .= 'define(\'YAWF_PACKED\', true);';
	$minified[]  = $path['dir-data'].'/lib/include/constants.php';
	$minified[]  = $path['dir-data'].'/lib/include/core.php';
	$minified[]  = $path['dir-data'].'/lib/include/functions.php';
	$minified[]  = $path['dir-data'].'/lib/include/aliases.php';
	$minified[]  = '}';

	$total = count($minified);

	foreach($minified as $key=>$file) {
		$code .= str_replace(array('<?php', '<?', "\t", "\n"), '', Compiler::compress_php_src($file));
		$make->progress($key+1, $total);
	}

	file_put_contents(WORKING_FILE_SYSTEM_CLASSES, $code);
	Compiler::swap('minified', $minified);
	return file_exists(WORKING_FILE_SYSTEM_CLASSES);
});



// Delete optimized files
$msg = 'Deleting minified files';
$result = $result && Compiler::process('compile.post', $msg, array(), function($make, $data) {
	$minified = Compiler::swap('minified');
	$total = count($minified);
	$make->progress(0, $total);

	foreach ($minified as $key=>$file) {
		@unlink($file);
		$make->progress($key+1, $total);
	}

	exec('rm -R '.WORKING_DIR_CLASSES);
	return !is_dir(WORKING_DIR_CLASSES);
});

