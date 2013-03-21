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

	if (!$is_helper && file_exists($f = ROOT."/lib/class/".$file)) {
		$found = include_once($f);
	}

	if (!$found && $is_helper) {
		$file = explode('/', $file);
		unset($file[0]);
		$file = implode('/', $file);

		if (file_exists($f = ROOT."/lib/helper/".$file)) {
			$found = include_once($f);
		}
	}

	$cname = ucfirsts(members_to_path(explode('\\', $class_name)), '::', '::');

	if ($found) {
		method_exists($class_name, 'autoinit') && $class_name::autoinit();
	} else {
		throw new System\Error\File(sprintf('Class or interface "%s" was not found. Expected on path "%s"', $cname, $f));
	}

	if (!class_exists($class_name) && !interface_exists($class_name)) {
		throw new System\Error(sprintf('Class or interface "%s" was expected in "%s" but not found.', $cname, $file));
	}
}
