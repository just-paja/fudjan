<?

/** Real core of pwf
 * @package core
 */

/** Class autoloader
 * @param string $class_name
 * @return void
 */
function __autoload($class_name)
{
	$found = false;
	$file = \System\Loader::get_class_file_name($class_name, true);
	$helper_pos = strpos(\System\Loader::get_link_from_class($class_name), 'helper');
	$is_helper = $helper_pos !== false && $helper_pos <= 1;

	$classes = \System\Composer::list_dirs('/lib/class');

	foreach ($classes as $dir) {
		if (!$is_helper && file_exists($f = $dir.'/'.$file)) {
			$found = include_once($f);
		}
	}

	if (!$found && $is_helper) {
		$helpers = \System\Composer::list_dirs('/lib/helper');

		$file = explode('/', $file);
		unset($file[0]);
		$file = implode('/', $file);

		foreach ($helpers as $dir) {
			if (file_exists($f = $dir.'/'.$file)) {
				$found = include_once($f);
			}
		}
	}
}
