<?php

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
			self::basic();
			Cache::init();
			Database::init();
		}


		/** Basic initialization - preload settings and locales
		 * @return void
		 */
		public static function basic()
		{
			Loader::init();
			Status::init();
			Settings::init();
			Locales::init();
			Router::update_rewrite();
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

			$dirs = \System\Composer::list_dirs('/etc/init.d');

			foreach ($list as $init_step) {
				$found = false;

				foreach ($dirs as $dir) {
					if (file_exists($f = $dir.'/'.$init_step.'.php')) {
						$found = true;
						require_once($f);
						break;
					}
				}

				if (!$found) {
					throw new \System\Error\File(sprintf("Init file '%s' was not found inside init folder '%s'.", $init_step, \System\Init::DIR_INIT));
				}
			}
		}
	}
}
