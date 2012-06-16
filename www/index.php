<?

/* Yet Another Content Management System */
/*              Index file               */


if (isset($argv[1])) {
	$request = '/' . isset($argv[1]) ? ltrim($argv[1], '/') : '/';
	$_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'] = $request;
}

define("ROOT",  __DIR__);
define("SETUP", isset($_GET['force_setup']));

require_once ROOT."/etc/init.d/core.php";
require_once ROOT.(SETUP === true ? "/etc/init.d/setup.php":"/etc/init.d/page.php");

