<?php

namespace Helper\Cli\Module
{
	class Assets extends \Helper\Cli\Module
	{
		protected static $info = array(
			'name' => 'assets',
			"head" => array(
				'Manage bower assets',
				'Read settings from your environment and perform actions for assets'
			),
		);


		protected static $attrs = array(
			"json"       => array("type" => 'bool', "value" => false, "short" => 'j', "desc"  => 'Output assets list in json'),
			"help"       => array("type" => 'bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose"    => array("type" => 'bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);

		protected static $commands = array(
			"list"   => array('List direct bower dependencies'),
			"update" => array('Update all predefined assets'),
		);

		/** Print basic system info to STDOUT
		 * @return void
		 */
		public static function cmd_update()
		{
			\System\Init::basic();

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
				\Helper\Cli::out('Running bower update');
				passthru('cd '.BASE_DIR.'; bower update');
			} else {
				\Helper\Cli::out('Please install node.js#bower first');
			}

			unlink(BASE_DIR.'/.bowerrc');
			unlink(BASE_DIR.'/bower.json');
		}


		public function cmd_list()
		{
			\System\Init::basic();

			$list = cfg('assets', 'dependencies');

			if ($this->json) {
				\Helper\Cli::out(json_encode(array("dependencies" => $list), JSON_PRETTY_PRINT));
			} else {
				\Helper\Cli::out('Bower dependencies');
				\Helper\Cli::out_flist(array(
					"list" => $list,
					"margin" => 2
				));
			}
		}
	}
}
