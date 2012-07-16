<?

namespace System
{
	abstract class Input
	{
		const EXEC_DIR = "/lib/exec";
		static $input;
		static $exec_status = array();


		static function init()
		{
			foreach ($_FILES as $var=>$cont) {
				if (isset($cont['name']) && is_array($cont['name'])) {
					foreach ($cont as $attr=>$value) {
						foreach ($value as $file=>$file_attr) {
							if ($a = is_array($file_attr)) {
								$_FILES[$var][$file][$attr] = $file_attr['file'];
							} else {
								$_FILES[$var][$attr] = $file_attr;
							}
						}
						
						if ($a) unset($_FILES[$var][$attr]);
					}
				} else {
					break;
				}
			}

			self::$input = array_merge_recursive((array) $_GET, (array) $_POST, (array) $_FILES);
			self::$input['path'] = $_SERVER['REQUEST_URI'];
			self::fix_input(self::$input);
			unset($_GET, $_POST);
		}


		static function get()
		{
			if (!func_num_args()) return self::$input;
			$path = func_get_args();

			if(is_array(func_get_arg(0))) {
				$args = array_shift($path);
				$path = array_merge($args, $path);
			}

			$last = end($path);
			if ($last === true) $raw = array_pop($path);
			else $raw = false;

			$iter = &self::$input;
			foreach($path as $arg) {
				if (isset($iter) && is_array($iter)) $iter = &$iter[$arg];
				else $iter = array();
			}

			return $raw ? $iter:self::secure($iter);
		}


		static function secure($str)
		{
			$bad = array("'", "`", "\"");
			$good = array("&#39;", "&#96;", "&quot;");

			return $str = str_replace($bad, $good, $str);
		}


		static function get_by_prefix($prefix)
		{
			$data = array();
			foreach (self::$input as $k=>&$v) {
				if (strpos($k, $prefix) === 0) $data[str_replace($prefix, '', $k)] = &$v;
			}

			return $data;
		}


		static function add(array $path, $what)
		{
			$iter = &self::$input;
			foreach($path as $arg){
				if(!is_array($iter) && ($i += 1) < count($path) && !isset($iter)) $iter = array();
				$iter = &$iter[$arg];
			}
			$iter[] = $what;
		}


		static function exec()
		{
			if (any(self::$input['exec'])) {
				self::rebool(self::get('bools'));
				foreach (self::$input['exec'] as $e) {
					$file = ROOT.self::EXEC_DIR.'/'.str_replace('..', '', $e).'.php';
					self::$exec_status[$e] = file_exists($file) ? !!include($file):false;
					Status::log('in_exec', array($file), self::$exec_status[$e]);
				}
			}
			return self::exec_check();
		}


		static function exec_check()
		{
			foreach (self::$exec_status as $stat) {
				if ($stat !== true) { return false; }
			}
			return true;
		}


		static function rebool($what)
		{
			$vars = explode(':', $what);
			foreach ($vars as $var) {
				$path = explode('[', str_replace(']', '', $var));
				$v = &self::get($path, true);
				$v = !!$v;
			}
		}


		static function rejson(array $path)
		{
			$set = &self::get($path, true);
			foreach ($set as &$var) {
				if (preg_match("/^json\:/", $var)) {
					$var = json_decode(substr($var, 5), true);
				}
			}
		}


		private static function fix_input(array &$data)
		{
			if (get_magic_quotes_gpc()) {
				foreach ($data as &$row) {
					if (is_array($row)) {
						self::fix_input($row);
					} else {
						$row = stripcslashes($row);
					}
				}
			}
		}
	}
}
