<?

/** Functions that are not to be categorized under class
 * @package core
 */
namespace
{
	/** Does variable contain something?
	 * @param &array $var
	 * @return bool
	 */
	function any(&$var)
	{
		return !empty($var);
	}


	/** Returns first item from array
	 * @param array $array
	 * @return mixed
	 */
	function first(array $array)
	{
		return reset($array);
	}


	/** Returns last item from array
	 * @param array $array
	 * @return mixed
	 */
	function last(array $array)
	{
		return $array[count($array) - 1];
	}


	/** Returns first key in array
	 * @param array $array
	 * @return string
	 */
	function first_key(array $array)
	{
		$keys = array_keys($array);
		return reset($keys);
	}


	/** Clear url slashes
	 * @param string|array $url
	 * @return string
	 */
	function clear_url($url)
	{
		if(!is_array($url)) $url = explode('/', $url);
		return implode('/', $url);
	}


	/** Reference wrapper for clear_url
	 * @param string $url
	 * @return void
	 */
	function clear_this_url(&$url)
	{
		$url = clear_url($url);
	}


	/** Get class path
	 * @param array $members
	 * @return string
	 */
	function members_to_path(array $members)
	{
		return strtolower(is_array($members) ? implode('::', $members):$members);
	}


	/** Dump function - displays debug info on all arguments
	 * @return void
	 */
	function v()
	{
		$trace = debug_backtrace();

		foreach (func_get_args() as $var) {
			$path = '';

			if (isset($trace[0]['file'])) {
				$path .= basename($trace[0]['file']);

				if (isset($trace[0]['line'])) {
					$path .= ":".$trace[0]['line'];
				}

				$path .= ", ";
			}

			if (isset($trace[0]['class'])) {
				$path .= $trace[0]['class'].'::'.$trace[0]['function']."()";
			} elseif (isset($trace[0]['function'])) {
				$path .= $trace[0]['function']."()";
			}

			echo '<div class="debug dump"><b>'.$path."</b><pre>";
				function_exists('var_export') && !is_string($var) ? var_dump($var):print_r($var);
			echo '</pre></div>';
		}
	}


	/** Generate random string
	 * @param int $length
	 * @return string
	 */
	function gen_random_string($length = 64)
	{
		$str = md5(intval(strval(rand(1,1000)*rand(1,1000)/rand(1,1000)*rand(1,1000))));
		if(strlen($str) > $length) $str = substr($str, 0, $length -1);
		return $str;
	}


	/** Collect is improved array_map, it is able to run method of object
	 * @param array $func  Description of required data
	 *                     Examples
	 *                       array('attr', 'id')
	 *                       array('this', 'get_seoname')
	 *
	 * @param  array $array      Set of objects or arrays
	 * @param  bool  $dont_assoc Return indexed array
	 * @return array Set of elements
	 */
	function collect($func, $array, $dont_assoc = false)
	{
		if (is_array($func)) {
			if ($func[0] == 'this') {
				$names = array();
				array_shift($func);

				foreach ($array as $key=>&$obj) {
					$f = implode('::', $func);

					if ($dont_assoc) {
						$names[] = $obj->$f();
					} else {
						$names[$key] = $obj->$f();
					}
				}

				return $names;
			} elseif ($func[0] == 'attr') {
				$names = array();

				foreach ($array as &$obj) {
					if (is_object($obj)) {
						if ($dont_assoc) {
							$names[] = $obj->$func[1];
						} else {
							$names[$obj->id] = $obj->$func[1];
						}
					} elseif (is_array($obj)) {
						$names[] = $obj[$func[1]];
					} else $names[] = $obj;
				}

				return $names;
			} else {
				return array_map($func, $array);
			}
		} elseif(is_callable($func)) {
			foreach ($array as &$item) {
				$item = $func($item);
			}
		} else {
			$names = array();

			foreach ($array as $item) {
				$names[] = $item[$func];
			}

			return $names;
		}

		return $array;
	}


	/** Pair data key:value 1:1
	 * @param mixed $func_keys Function to pair keys
	 * @param mixed $func_data Function to pair data
	 * @param array $data
	 * @return array
	 */
	function collect_pair($func_keys, $func_data, array $data)
	{
		$keys = collect($func_keys, $data, true);
		$data = collect($func_data, $data, true);
		return empty($keys) ? array():array_combine($keys, $data);
	}


	/** Preset variable with default value if not set
	 * @param &mixed $var     Variable to set
	 * @param  mixed $def_val Default value to set
	 */
	function def(&$var, $def_val = null)
	{
		return is_null($var) ? ($var = $def_val):$var;
	}


	/** Print data by keys into string
	 * @param string $str    String filled with {keys}
	 * @param array  $data   Set of data
	 * @param bool   $strict Remove unused keys
	 * @param string $prefix Key prefix
	 */
	function stprintf($str, array $data = array(), $strict = true, $prefix = null)
	{
		if ($str === null)
			return null;

		$keys = array('\{', '\}');
		$vals = array('', '');

		foreach($data as $k => $v) {
			if (is_object($v)) {
				$v = '{object}';
			} elseif (is_array($v)) {
				$v = implode(', ', $v);
			}

			$keys[] = '{'.($prefix ? $prefix.'_':'').$k.'}';
			$vals[] = $v;
		}

		$str = str_replace($keys, $vals, $str);
		return $strict ? preg_replace("/\{[a-zA-Z\-\_]\}/", "", $str):$str;
	}


	/** Object version of stprintf - handles System\Model\Attr models
	 * @param string $str
	 * @param System\Model\Attr $object
	 * @param bool $strict
	 * @param string $prefix
	 */
	function soprintf($str, System\Model\Attr $object, $strict = true, $prefix = null)
	{
		$data = array_merge($object->get_opts(), $object->get_data());

		if ($object instanceof System\Model\Database) {
			$data[System\Model\Database::get_id_col(get_class($object))] = $object->id;
		}

		if (!$object->has_attr('seoname')) {
			$data['seoname'] = $object->get_seoname();
		}

		return stprintf($str, $data, $strict, $prefix);
	}


	/** Make all first letters uppercase
	 * @param string $str         Make all first letters in string capital
	 * @param string $separator   Separator to distinct words
	 * @param string $replacement Glue
	 */
	function ucfirsts($str, $separator = '-', $replacement = ' ')
	{
		$temp = explode($separator, $str);
		foreach ($temp as &$part) {
			$part = cflc($part);
		}
		return implode($replacement, $temp);
	}


	/** Change first letter case into whatever
	 * @param string $str
	 * @param int    $case   String case from System\Template
	 * @return string
	 */
	function cflc($str, $case = System\Template::CASE_UPPER)
	{
		if (is_array($str)) {
			foreach ($str as &$s) {
				$s = cflc($s);
			}
		} else {
			$str = mb_convert_case(mb_substr($str, 0, 1), $case).mb_substr($str, 1);
		}
		return $str;
	}


	/** Reads contents of directory
	 * @param string $dir
	 * @param array $files
	 * @param array $directories
	 * @param array $used
	 * @deprecated Replaced by System\Directory::find
	 * @return void
	 */
	function read_dir_contents($dir, array &$files = array(), array &$directories = array(), array &$used = array())
	{
		$od = opendir($dir);
		while ($f = readdir($od)) {
			if ($f != '.' && $f != '..') {
				$fp = $dir.'/'.$f;
				if (is_dir($fp)) {
					read_dir_contents($fp, $files, $directories, $used);
					if (!in_array($fp, $used)) {
						$directories[] = $fp;
						$used[] = $fp;
					}
				} else {
					if (!in_array($fp, $used)) {
						$files[] = $fp;
						$used[] = $fp;
					}
				}
			}
		}
		closedir($od);
	}


	/** Generate hash of a password
	 * @param string $str
	 * @return string
	 */
	function hash_passwd($str)
	{
		return sha1(cfg('site', 'password', 'shield').md5($str));
	}


	/** Collect ids of all database models in list
	 * @param array $list
	 */
	function collect_ids(array $list) {
		return collect(array('attr', 'id'), $list);
	}


	/** Collect names of all database models in list
	 * @param array $list
	 */
	function collect_names(array $list) {
		return collect(array('this', 'get_name'), $list);
	}


	/** Transform into std argument array
	 * @return array
	 */
	function args()
	{
		return array("args" => func_get_args());
	}


	function get_model($obj)
	{
		if (is_object($obj)) {
			$model = get_class($obj);
		} else if (is_string($obj)) {
			$model = strpos($obj, '\\') === 0 ? $obj:('\\'.$obj);
		}

		return $model;
	}
}
