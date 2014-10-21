<?

namespace System\Cli\Module
{
	class Test extends \System\Cli\Module
	{
		protected static $info = array(
			'name' => 'test',
			'head' => array(
				'Test your application',
			),
		);


		protected static $attrs = array(
			"help"    => array('bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose" => array('bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);


		protected static $commands = array(
			"all" => array('Run all tests'),
		);


		public static function cmd_all()
		{
			\System\Init::basic();

			$all = self::get_all();
			$cmd = implode(';', array(
				"cd '".BASE_DIR."'",
				"phpunit --bootstrap 'etc/init.d/test.php' --colors --test-suffix .php ".implode(" ", $all)
			));

			$out = passthru($cmd);
		}


		private static function get_all()
		{
			return \System\Composer::list_dirs('/lib/test');
		}

	}
}
