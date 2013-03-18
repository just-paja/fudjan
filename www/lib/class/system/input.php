<?

/** @package system
 */
namespace System
{
	/** Input class. Manages get and post data inputs by escaping them and storing them
	 * @package system
	 * @property array $input
	 */
	abstract class Input
	{
		/** @type Input storage */
		private static $input;


		/** Public init
		 * @return void
		 */
		public static function init()
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

			if (!isset($_GET)) $_GET = array();
			if (!isset($_POST)) $_POST = array();

			self::$input = array_merge_recursive((array) $_GET, (array) $_POST, (array) $_FILES);
			self::$input['request'] = $_SERVER['REQUEST_URI'];
			self::$input['path']    = explode('?', $_SERVER['REQUEST_URI'], 2);
			self::$input['path']    = self::$input['path'][0];
			self::fix_input(self::$input);
			unset($_GET, $_POST);
		}


		/** Get input data
		 * @usage get(path, path, path, ..)
		 * @param array|string $path Path of input
		 * @return mixed
		 */
		public static function get($path = null)
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


		/** Escape input
		 * @param string $str
		 * @return string
		 */
		public static function secure($str)
		{
			$bad = array("'", "`", "\"");
			$good = array("&#39;", "&#96;", "&quot;");

			return $str = str_replace($bad, $good, $str);
		}


		/** Get data with key, that starts with specific prefix
		 * @param string $prefix
		 * @return array
		 */
		public static function get_by_prefix($prefix)
		{
			$data = array();
			foreach (self::$input as $k=>&$v) {
				if (strpos($k, $prefix) === 0) $data[substr($k, strlen($prefix))] = &$v;
			}

			return $data;
		}


		/** Add data into input class. No matter why.
		 * @param array $path
		 * @param mixed $what
		 * @return void
		 */
		public static function add(array $path, $what)
		{
			$iter = &self::$input;
			foreach($path as $arg){
				if(!is_array($iter) && ($i += 1) < count($path) && !isset($iter)) $iter = array();
				$iter = &$iter[$arg];
			}
			$iter[] = $what;
		}


		/** Strip slashes if magic quotes are on
		 * @param array &$data
		 * @return void
		 */
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
