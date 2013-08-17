<?

/** System static init
 * @package system
 */
namespace System
{
	/** System static init
	 * @package system
	 */
	class Init
	{
		const DIR_INIT = '/etc/init.d';

		/** Full initialization - preload settings, locales, cache and database
		 * @return void
		 */
		public static function full()
		{
			self::bind_error_handlers();
			Settings::init();
			Locales::init();
			Cache::init();
			Database::init();
		}

		/** Basic initialization - preload settings and locales
		 * @return void
		 */
		public static function basic()
		{
			self::bind_error_handlers();
			Settings::init();
			Locales::init();
		}


		/** CLI initialization
		 * @return void
		 */
		public static function cli()
		{
			global $argv;
			$last = end($argv);
			$_SERVER['REQUEST_URI'] = $last == 'index.php' ? '/':$last;

			php_sapi_name() != 'cli' && give_up("This program can be run only via PHP CLI !!");

			!class_exists("CLIOptions")  && give_up("Missing class 'CLIOptions' !!");
			!class_exists("CLICommands") && give_up("Missing class 'CLICommands'!!");

			require_once ROOT."/lib/include/functions.cli.php";

			\CLIOptions::init();
			\CLIOptions::parse_options();

			require_once ROOT."/etc/init.d/core.php";

			$cmd = \CLIOptions::get('command');
			\CLICommands::$cmd();
		}

		/** Bind pwf error handlers
		 * @return void
		 */
		public static function bind_error_handlers()
		{
			set_exception_handler(array("System\Status", "catch_exception"));
			set_error_handler(array("System\Status", "catch_error"));
			register_shutdown_function(array("System\Status", "catch_fatal_error"));

			ini_set('log_errors',     true);
			ini_set('display_errors', true);
			ini_set('html_errors',    false);
		}


		/** Run list of init scripts
		 * @param array $list   List of init scripts
		 * @param array $locals Local data
		 * @return void
		 */
		public static function run(array $list, array $locals)
		{
			// Convert locals into level on variables
			foreach ((array) $locals as $k=>$v) {
				$k = str_replace('-', '_', $k);
				$$k=$v;
			}

			foreach ($list as $init_step) {
				if (file_exists($f = ROOT.'/etc/init.d/'.$init_step.'.php')) {
					require_once($f);
				} else throw new \System\Error\File(sprintf("Init file '%s' was not found inside init folder '%s'.", $init_step, \System\Init::DIR_INIT));
			}
		}

	}
}
