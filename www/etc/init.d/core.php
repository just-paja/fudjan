<?

define("DIR_CLASS",    'lib/class');
define("DIR_MODULE",   'lib/module');
define("DIR_LOCALES",  'lib/locales');
define("DIR_TEMPLATE", 'lib/template');

ob_implicit_flush(false);

$Microtime = microtime(true);

require_once ROOT."/lib/include/functions.php";
require_once ROOT."/lib/include/core.php";
require_once ROOT."/lib/include/aliases.php";
require_once ROOT."/etc/init.d/session.php";

if (!defined("YACMS_ERROR_HANDLERS")) {

	define("YACMS_ERROR_HANDLERS", true);
	set_exception_handler(array("System\Status", "catch_exception"));
	//set_error_handler("handleError");

}

System\Settings::init();
System\Cache::init();
System\Locales::init();
System\Database::init();
System\Input::init();
System\Output::init();

error_reporting(E_ALL);
//error_reporting(E_ERROR);

if (System\Settings::get('dev', 'debug')) {
	ini_set('display_errors', true);
	ini_set('html_errors',    true);
} else {
	ini_set('log_errors',     true);
	ini_set('display_errors', false);
	ini_set('html_errors',    false);
}

