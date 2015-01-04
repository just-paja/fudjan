<?

namespace System\Cli\Module
{
	class Cache extends \System\Cli\Module
	{
		protected static $info = array(
			'name' => 'cache',
			'head' => array(
				'Manage your application cache',
			),
		);


		protected static $attrs = array(
			"help"    => array('bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose" => array('bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);


		protected static $commands = array(
			"core" => array('Cache application core for faster run'),
		);


		public static function cmd_core()
		{
			\System\Init::basic();
			\System\Loader::cache_core();
		}
	}
}
