<?

namespace System\Cli\Module
{
	class Assets extends \System\Cli\Module
	{
		protected static $info = array(
			'name' => 'assets',
			"head" => array(
				'Manage bower assets',
				'Read settings from your environment and perform actions for assets'
			),
		);


		protected static $attrs = array(
			"help"       => array('bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose"    => array('bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);

		protected static $commands = array(
			"update" => array('Update all predefined assets'),
		);

		/** Print basic system info to STDOUT
		 * @return void
		 */
		public static function cmd_update()
		{
			\System\Init::full();

			$deps = cfg('assets');
			$list = array();

			foreach ($deps as $dep_list) {
				$list = array_merge($list, $dep_list);
			}

			\System\Json::put(BASE_DIR.'/bower.json', array(
				"name" => "pwf-generic",
				"dependencies" => $list
			));

			\System\Json::put(BASE_DIR.'/.bowerrc', array("directory" => "share/bower"));

			$found = exec('which bower');

			if ($found) {
				\System\Cli::out('Running bower update');
				passthru('cd '.BASE_DIR.'; bower update');
			} else {
				\System\Cli::out('Please install node.js#bower first');
			}

			unlink(BASE_DIR.'/.bowerrc');
			unlink(BASE_DIR.'/bower.json');
		}
	}
}
