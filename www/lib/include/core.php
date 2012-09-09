<?

define("REDIRECT_AFTER_FLOW", 1);
define("REDIRECT_IMMEDIATELY", 2);
define("NL", "\n");

// Exceptions
class InternalException extends Exception
{
	protected $data = array();
	protected $backtrace = array();

	function __construct() {
		$d = func_get_args();

		if (isset($d[0]) && $d[0] === 'stack') {
			foreach ($d as $i=>$data) {
				if ($i != 0) {
					if (is_array($data)) {
						foreach ($data as $arg) {
							$this->data[] = $arg;
						}
					} else $this->data[] = $data;
				}
			}
		} else {
			$this->data = $d;
		}

		$backtrace = debug_backtrace();
		$len = count($backtrace);
		$i = $len >= 4 ? 3:0;

		for ($i; $i<$len; $i++) {
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


class DatabaseException extends InternalException
{
	function __construct()
	{
		$d = func_get_args();
		if (strpos(strtolower($d[0]), 'duplicate') !== false || (isset($d[1]) && strpos(strtolower($d[1]), 'duplicate') !== false)) {
			$e = l('Cannot insert data because of duplicate unique key!');
		} elseif (strpos(strtolower($d[0]), 'syntax') !== false) {
			$e = l('Cannot run query because of syntax error.');
		} else {
			$e = l('Unhandled error');
		}

		parent::__construct('stack', $e, $d);
	}
}

class CacheException extends InternalException {}
//class CatchableException extends InternalException {}
class DevelopmentException extends InternalException {}
class ArgumentException extends InternalException {}
class MissingArgumentException extends InternalException {}
class NestedModelException extends InternalException {}
class FatalException extends InternalException {}
class MissingFileException extends InternalException {}
class ConfigException extends InternalException {}
class DependencyException extends InternalException {}


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
