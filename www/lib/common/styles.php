<?

define('ROOT', realpath(__DIR__.'/../../'));
require_once ROOT."/etc/init.d/core.php";


define('DIR_ESSENTIALS', ROOT."/share/styles/essential");
define('DIR_MODULES', ROOT."/share/styles/modules");
header("Content-Type: text/css");


$mod_str = System\Input::get('modules');
$modules = empty($mod_str) ? array():explode(':', $mod_str);
$files = array();

if (is_dir(DIR_ESSENTIALS)) {
	$dir = opendir(DIR_ESSENTIALS);
	while ($f = readdir($dir)) {
		if (strpos($f, ".") !== 0) {
			$files[] = DIR_ESSENTIALS.'/'.$f;
		}
	}
}


if (is_dir(DIR_MODULES)) {
	foreach ($modules as $module) {
		if (is_file($p = DIR_MODULES.'/'.$module.'.css')) {
			$files[] = $p;
		} elseif (is_dir($p = DIR_MODULES.'/'.$module.'.d')) {
			read_dir_contents($p, $files);
		}
	}

	foreach ($files as $file)  {
		require_once $file;
	}
}
