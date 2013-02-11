<?

// Class autoloader
function __autoload($class_name)
{
	$found = false;
	// TODO: fix dibi!
	if (strpos($class_name, "Nette") !== false) return;
	if (strpos($class_name, "IBar") !== false) return;

	// TODO: rewrite this regexp
	$file = str_replace("\_", '/', substr(strtolower(preg_replace("/([A-Z])/", "_$1", $class_name)), 1)).".php";
	file_exists($f = ROOT."/lib/class/".$file) && $found = include($f);
	method_exists($class_name, 'autoinit') && $class_name::autoinit();

	if (!$found) {
		throw new System\Error\File('Class not found: \''.ucfirsts(members_to_path(explode('\\', $class_name)), '::', '::').'\', expected \'/'.$file.'\'');
	}

	if (!class_exists($class_name) && !interface_exists($class_name)) {
		throw new System\Error\Internal('Class or interface \''.ucfirsts(members_to_path(explode('\\', $class_name)), '::', '::').'\' was expected in \''.$f.'\'.');
	}

}
