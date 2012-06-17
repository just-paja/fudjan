<?

php_sapi_name() != 'cli' && give_up("This program can be run only via PHP CLI !!");

!class_exists("CLIOptions")  && give_up("Missing class 'CLIOptions' !!");
!class_exists("CLICommands") && give_up("Missing class 'CLICommands'!!");

require_once ROOT."/lib/include/constants.cli.php";
require_once ROOT."/lib/include/functions.cli.php";

CLIOptions::init();
CLIOptions::parse_options();
define("YACMS_ENV", CLIOptions::get_env());

require_once ROOT."/etc/init.d/core.php";

System\Output::set_format('cli');

$cmd = CLIOptions::get('command');
CLICommands::$cmd();


