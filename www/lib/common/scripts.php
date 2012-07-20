<?

define('ROOT', realpath(__DIR__.'/../../'));
require_once ROOT."/etc/init.d/core.php";


define('DIR_ESSENTIALS', ROOT."/share/scripts/essential");
define('DIR_MODULES', ROOT."/share/scripts/modules");
header("Content-Type: text/javascript");


$mod_str = System\Input::get('modules');
$modules = empty($mod_str) ? array():explode(':', $mod_str);
$files = array();


if (is_dir(DIR_ESSENTIALS)) {
	$dir = opendir(DIR_ESSENTIALS);
	while ($f = readdir($dir)) {
		if (strpos($f, ".") !== 0) {
			$files[] = $f;
		}
	}

	sort($files);

	foreach ($files as $f) {
		include_once(DIR_ESSENTIALS.'/'.$f);
	}
}


if (is_dir(DIR_MODULES)) {
	foreach ($modules as $module) {
		if (file_exists($p = DIR_MODULES."/".$module . '.js')) {
			require_once($p);
		} else {
			echo 'clog("Module not found: '.$module.'");';
		}
	}
}
