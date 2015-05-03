<?

namespace Helper\Cli\Module
{
	class Cache extends \Helper\Cli\Module
	{
		protected static $info = array(
			'name' => 'cache',
			'head' => array(
				'Manage your application cache',
			),
		);


		protected static $attrs = array(
			"help"    => array("type" => 'bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose" => array("type" => 'bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);


		protected static $commands = array(
			"all"     => array('Build complete application cache'),
			"clean"   => array('Completely erase application cache'),
			"core"    => array('Cache application core for faster run'),
			"locales" => array('Cache application core for faster run'),
			"modules" => array('Cache application modules for faster run'),
			"static"  => array('Cache static files to minimize folder lookups'),
		);


		public function cmd_clean()
		{
			\System\Init::basic();
			\System\Cache::clear();
		}


		public function cmd_all()
		{
			\System\Init::basic();

			$this->cmd_core();
			$this->cmd_modules();
			$this->cmd_locales();
			$this->cmd_static();
		}


		public function cmd_core()
		{
			\System\Init::basic();

			\Helper\Cli::out('Building system core');
			\System\Cache::build_core();
		}


		public function cmd_modules()
		{
			\System\Init::basic();

			\Helper\Cli::out('Building system modules');
			\System\Cache::build_modules();
		}


		public function cmd_locales()
		{
			\System\Init::basic();

			\Helper\Cli::out('Building system locales');
			\System\Cache::build_locales();
		}


		public function cmd_static()
		{
			\System\Init::basic();

			$lib_list = \System\Composer::get_libs();
			$libs = array();

			foreach ($lib_list as $lib) {
				$libs[$lib] = $lib;
			}

			array_push($libs, null);

			\Helper\Cli::do_over($libs, function($key, $name) {
				\System\Cache::build_static_for($name);
			}, 'Collecting static files');
		}
	}
}
