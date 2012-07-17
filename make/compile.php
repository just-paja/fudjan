<?

$path = PackageInfo::get('path');
define('WORKING_DIR_CLASSES', $path['dir-data'].'/lib/class/system');
define('WORKING_FILE_SYSTEM_CLASSES', $path['dir-data'].'/lib/include/system.php');

PackageInfo::$files[] = 'lib/include/system.php';

// Optimize system code
$msg = 'Compiling system code';
show_progress_cli(0, 100, CONSOLE_WIDTH, '', $msg);

$code  = '<?';
$used = $minified = array(
	WORKING_DIR_CLASSES.'/model/attr.php',
	WORKING_DIR_CLASSES.'/model/callback.php',
	WORKING_DIR_CLASSES.'/model/basic.php',
);
$dirs = array();

read_dir(WORKING_DIR_CLASSES.'/model', $minified, $dirs, $used);
read_dir(WORKING_DIR_CLASSES, $minified, $dirs, $used);

$minified[]  = 'namespace{';
$minified[] .= 'define(\'YAWF_PACKED\', true);';
$minified[]  = $path['dir-data'].'/lib/include/core.php';
$minified[]  = $path['dir-data'].'/lib/include/functions.php';
$minified[]  = $path['dir-data'].'/lib/include/aliases.php';
$minified[]  = '}';

$total = count($minified);

foreach($minified as $key=>$file) {
	$code .= str_replace(array('<?php', '<?', "\t", "\n"), '', compress_php_src($file));
	show_progress_cli($key+1, $total, CONSOLE_WIDTH, '', $msg);
}

file_put_contents(WORKING_FILE_SYSTEM_CLASSES, $code);



// Delete optimized files
$msg = 'Deleting minified files';
show_progress_cli(0, $total, CONSOLE_WIDTH, '', $msg);

foreach ($minified as $key=>$file) {
	@unlink($file);
	show_progress_cli($key+1, $total, CONSOLE_WIDTH, '', $msg);
}

exec('rm -R '.WORKING_DIR_CLASSES);
PackageInfo::swap('minified', $minified);
