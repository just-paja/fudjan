<?


define('ROOT', realpath(__DIR__.'/../../'));
require_once ROOT."/etc/init.d/core.php";

System\Init::basic();
System\Resource::request();
