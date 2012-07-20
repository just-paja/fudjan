<?

header("Content-Type: text/javascript");
define('DIR_ESSENTIALS', __DIR__."/essential");
define('DIR_MODULES', __DIR__."/modules");

$modules = empty($_GET['modules']) ? array():explode(';', $_GET['modules']);
$files = array();

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

foreach ($modules as $module) {
	if (file_exists($p = DIR_MODULES."/".$module . '.js')) {
		require_once($p);
	} else {
		echo 'clog("Module not found: '.$module.'");';
	}
}
