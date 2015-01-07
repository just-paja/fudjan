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
			"all"    => array('Build complete application cache'),
			"clear"  => array('Completely erase application cache'),
			"core"   => array('Cache application core for faster run'),
			"static" => array('Cache static files to minimize folder lookups'),
		);


		public function cmd_clear()
		{
			\System\Init::basic();
			\System\Cache::clear();
		}


		public function cmd_all()
		{
			\System\Init::basic();

			$this->cmd_core();
			$this->cmd_static();
		}


		public function cmd_core()
		{
			\System\Init::basic();

			\System\Cli::out('Building system core');
			\System\Cache::build_core();
		}


		public function cmd_static()
		{
			\System\Init::basic();

			$lib_list = \System\Composer::get_libs();
			$libs = array();

			foreach ($lib_list as $lib) {
				$libs[$lib] = $lib;
			}

			\System\Cli::do_over($libs, function($key, $name) {
				\System\Cache::build_static_for($name);
			}, 'Collecting static files');
		}
	}
}
