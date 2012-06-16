<?

define("REDIRECT_AFTER_FLOW", 1);
define("REDIRECT_IMMEDIATELY", 2);
define("NL", "\n");

// Exceptions
class InternalException extends Exception
{
	private $data = array();
	private $backtrace = array();

	function __construct() {
		$this->data = func_get_args();
		$backtrace = debug_backtrace();
		$len = count($backtrace);

		for ($i=3; $i<$len; $i++) {
			$target = &$this->backtrace[];
			$target = array();

			isset($backtrace[$i]['file']) && $target['file'] = '    '.$backtrace[$i]['file'].(isset($backtrace[$i]['line']) ? ':'.$backtrace[$i]['line']:'');
			isset($backtrace[$i]['class']) && $target['class'] = '   '.$backtrace[$i]['class'];
			isset($backtrace[$i]['function']) && $target['function'] = $backtrace[$i]['function'];

			if ($i >= 6) {
				break;
			}
		}
	}
	function get_explanation() { return $this->data; }
	function get_backtrace() { return $this->backtrace; }
}

class CacheException extends InternalException {}
class CatchableException extends InternalException {}
class MissingArgumentException extends InternalException {}
class NestedModelException extends InternalException {}
class FatalException extends InternalException {}
class MissingFileException extends InternalException {}
class ConfigException extends InternalException {}


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
		throw new MissingFileException('Class not found: \''.ucfirsts(members_to_path(explode('\\', $class_name)), '::', '::').'\', expected \'/'.$file.'\'');
	}

	if (!class_exists($class_name) && !interface_exists($class_name)) {
		throw new FatalException('Class or interface \''.ucfirsts(members_to_path(explode('\\', $class_name)), '::', '::').'\' was expected in \''.$f.'\'.');
	}

}
