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

			$this->vout('Building system core');
			\System\Cache::build_core();
		}


		public function cmd_static()
		{
			\System\Init::basic();

			$this->vout('Building static cache');
			\System\Cache::build_static();
		}
	}
}
