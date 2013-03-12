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

	file_exists($f = ROOT."/lib/class/".$file) && $found = include($f);
	method_exists($class_name, 'autoinit') && $class_name::autoinit();

	$cname = ucfirsts(members_to_path(explode('\\', $class_name)), '::', '::');

	if (!$found) {
		throw new System\Error\File(sprintf('Class or interface "%s" was not found. Expected on path "%s"', $cname, $file));
	}

	if (!class_exists($class_name) && !interface_exists($class_name)) {
		throw new System\Error\Internal(sprintf('Class or interface "%s" was expected in "%s" but not found.', $cname, $file));
	}
}
