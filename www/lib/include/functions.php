<?

/** Just alias - looks better
 * @param &array $var
 * @returns bool
 */
function any(&$var)
{
	return !empty($var);
}


/** Returns first item from array - wrapper that does not return reference
 * @param array $array
 * @returns mixed
 */
function first(array $array)
{
	return reset($array);
}


/** Returns first key in array
 * @param array $array
 * @returns string
 */
function first_key(array $array)
{
	$keys = array_keys($array);
	return reset($keys);
}


/** Clear url slashes
 * @returns string
 */
function clear_url($url, $mode=null)
{
	if(!is_array($url)) $url = explode('/', $url);
	//$url = array_filter($url);
//	if(!$mode) array_unshift($url, null); 
	return implode('/', $url);
}


/** Reference wrapper for clear_url
 * @returns void
 */
function clear_this_url(&$url, $mode=null)
{
	$url = clear_url($url, $mode);
}


/** Get class path
 * @param array $members
 * @returns string
 */
function members_to_path(array $members)
{
	return strtolower(is_array($members) ? implode('::', $members):$members);
}


/** Print nice output of arguments
 * @return mixed
 */
function dump()
{
	foreach (func_get_args() as $var) {
		if (cfg('dev', 'debug') || defined("YACMS_INSTALLER")){
			$trace = debug_backtrace();
			echo '<div class="debug dump"><b>'.basename($trace[0]['file']).":".$trace[0]['line'].", ".(@$trace[1]['class']).'::'.$trace[1]['function']."()"."</b><pre>";
				function_exists('var_export') && !is_string($var) ? var_export($var):print_r($var);
			echo '</pre></div>';
		}
	}

	return func_num_args() > 1 ? func_get_args():$var;
}


// paths
function &array_get(&$iter, $path)
{
	if (is_array($path)) {
		$key = array_shift($path);
		$iter = array_get($storage[$key], $path);
	} else {
		return $iter[$path];
	}
}


/** Generate random string
 * @param int $length
 * @returns string
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
			array_shift($func);
			foreach ($array as &$obj) {
				$f = implode('::', $func);
				$obj->$f();
			}
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
 * @returns array
 */
function collect_pair($func_keys, $func_data, array $data)
{
	$keys = collect($func_keys, $data, true);
	$data = collect($func_data, $data, true);
	return empty($keys) ? array():array_combine($keys, $data);
}


/** Format and translate datetime format
 * @param mixed  $date
 * @param string $format Format name
 * @returns string
 */
function format_date($date, $format = 'std')
{
	if ($date instanceof DateTime) {
		$d = $date->format(System\Locales::get('date:'.$format));
		return strpos($format, 'html5') === 0 ? $d:translate_date($d);
	} elseif(is_numeric($date)) {
		$d = date(System\Locales::get('date:'.$format), $date);
		return strpos($format, 'html5') === 0 ? $d:translate_date($d);
	} else {
		return $date;
	}
}


/** Get icon of module
 * @param string $module_namespace
 * @param string $prefix
 * @param array  $attrs HTML attributes for icon
 */
function module_icon($module_namespace, $prefix = null, $size = 32, array $attrs = array())
{
	return file_exists(System\Template::ICONS_DIR.'/'.$size.'/modules/'.$prefix.'-'.$module_namespace.'.png') ?
		icon('modules/'.$prefix.'/'.$module_namespace, $size, array("title" => ucfirst($prefix.'/'.$module_namespace))):
		icon('modules/'.$module_namespace, $size, array("title" => ucfirst(($prefix ? $prefix.'/':null).$module_namespace)));
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
		if ($v instanceof DateTime) {
			$v = format_date($v, 'human');
		} elseif (is_object($v)) {
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

	if (!$object->attr_exists('seoname')) {
		$data['seoname'] = $object->get_seoname();
	}

	return stprintf($str, $data, $strict, $prefix);
}


/** Make all first letters uppercase
 * @param string $str
 * @param string $separator
 * @param string $separator replacement
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
 * @param string $string
 * @param int    $case   String case from System\Template
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
 * @param &array $files
 * @param &array $directories
 * @param &array $used
 * @return void
 */
function read_dir_contents($dir, array &$files, array &$directories, array &$used = array())
{
	$od = opendir($dir);
	while ($f = readdir($od)) {
		if ($f != '.' && $f != '..') {
			$fp = $dir.'/'.$f;
			if (is_dir($fp)) {
				read_dir($fp, $files, $directories);
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
