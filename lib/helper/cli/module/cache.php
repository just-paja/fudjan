<?php

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
			"purge"   => array("type" => 'bool', "value" => false, "short" => 'p', "desc" => 'Purge persistent cache'),
			"verbose" => array("type" => 'bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);


		protected static $commands = array(
			"all"     => array('Build complete application cache'),
			"clean"   => array('Completely erase application cache'),
			"core"    => array('Cache application core for faster run'),
			"locales" => array('Cache application core for faster run'),
			"modules" => array('Cache application modules for faster run'),
			"static"  => array('Cache static files to minimize folder lookups'),
			"retire"  => array('Increment resource serial number by one retiring resources in public cache'),
		);


		/**
		 * Delete cached files from temporary cache. Accepts purge parameter to
		 * delete persistent files as well.
		 *
		 * @return void
		 */
		public function cmd_clean()
		{
			\System\Init::basic();
			\System\Cache::clear($this->purge);
		}


		/**
		 * Create local cache of everything possible.
		 *
		 * @return void
		 */
		public function cmd_all()
		{
			\System\Init::basic();

			$this->cmd_core();
			$this->cmd_modules();
			$this->cmd_locales();
			$this->cmd_static();
		}


		/**
		 * Build system core cache
		 *
		 * @return void
		 */
		public function cmd_core()
		{
			\System\Init::basic();

			\Helper\Cli::out('Building system core');
			\System\Cache::build_core();
		}


		/**
		 * Build modules cache
		 *
		 * @return void
		 */
		public function cmd_modules()
		{
			\System\Init::basic();

			\Helper\Cli::out('Building system modules');
			\System\Cache::build_modules();
		}


		/**
		 * Build locales cache
		 *
		 * @return void
		 */
		public function cmd_locales()
		{
			\System\Init::basic();

			\Helper\Cli::out('Building system locales');
			\System\Cache::build_locales();
		}


		/**
		 * Build static cache
		 *
		 * @return void
		 */
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


		public function cmd_retire()
		{
			if (\System\Settings::get('dev', 'debug', 'backend') || \System\Settings::get('dev', 'disable', 'serial')) {
				return;
			}

			$serial = \System\Settings::get('cache', 'resource', 'serial');
			\System\Settings::set(array('cache', 'resource', 'serial'), $serial + 1);
			\System\Settings::save('cache');
		}
	}
}
