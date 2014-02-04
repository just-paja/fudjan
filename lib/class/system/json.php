<?

namespace System
{
	class Json
	{
		private static $errors = array(
			JSON_ERROR_DEPTH           => 'The maximum JSON stack depth has been exceeded',
			JSON_ERROR_STATE_MISMATCH  => 'Invalid or malformed JSON string',
			JSON_ERROR_CTRL_CHAR       => 'JSON control character error, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX          => 'Syntax error in JSON string',
			JSON_ERROR_UTF8            => 'Malformed UTF-8 characters in JSON string, possibly incorrectly encoded',
		);


		/** JSON error safe string decode
		 * @param string $str    String to be parsed
		 * @param bool   $silent Don't throw any error exceptions
		 * @returns mixed
		 */
		public static function decode($str, $silent = false)
		{
			$json = json_decode($str, true);

			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch (\System\Error $e) { $debug = true; }

			/// Skipping this error as if nothing happened on production. The application will not failexit, although there will be no data.
			if (!$silent && ($err = json_last_error()) !== JSON_ERROR_NONE && $debug) {
				throw new \System\Error\Format(self::get_error($err), $str);
			}

			return $json;
		}


		/** Read JSON from file on path
		 * @param string $path
		 * @return array|false
		 */
		public static function read($path, $silent = false)
		{
			return self::decode(\System\File::read($path, $silent));
		}


		/** Put data formatted in JSON to path
		 * @param string $path
		 * @param mixed  $json
		 * @return bool
		 */
		public static function put($path, $json)
		{
			return \System\File::put($path, json_encode($json));
		}


		/** Read all JSON files in dir and return content
		 * @param string $dir_dist   Path to directory
		 * @param &array $temp       Variable to write in
		 * @param bool   $assoc_keys Associate file names (without postifx) to data read
		 * @param &array $files      Files that have been read during dist search
		 * @return array
		 */
		public static function read_dist($dir_dist, &$temp = array(), $assoc_keys = false, &$files = array())
		{
			if (\System\Directory::check($dir_dist, false)) {
				$dir = opendir($dir_dist);
				while ($f = readdir($dir)) {
					if (strpos($f, ".") !== 0 && strpos($f, ".json")) {
						list($mod) = explode(".", $f);
						$json = (array) self::decode(\System\File::read($dir_dist.'/'.$f));
						$files[] = $dir_dist.'/'.$f;

						if ($assoc_keys) {
							if (isset($temp[$mod])) {
								$temp[$mod] = array_replace_recursive($temp[$mod], $json);
							} else {
								$temp[$mod] = $json;
							}
						} else {
							$temp = array_merge_recursive($temp, $json);
						}
					}
				}

				closedir($dir);
				return $temp;
			} else throw new \System\Error\File(sprintf('Directory "%s" either does not exist or is not accessible.', $dir_dist));
		}

		public static function read_dist_all(array $dirs, $assoc_keys = false, &$files = array())
		{
			$temp = array();

			foreach ($dirs as $dir) {
				$temp = self::read_dist($dir, $temp, $assoc_keys, $files);
			}

			return $temp;
		}


		/** Get JSON error description
		 * @param int $id
		 * @return string
		 */
		private static function get_error($id)
		{
			return isset(self::$errors[$id]) ? self::$errors[$id]:'Unknown JSON error.';
		}


		/** Make JSON string human readable
		 * @source http://recursive-design.com/blog/2008/03/11/format-json-with-php/
		 * @param string $json
		 * @return string
		 */
		public static function json_humanize($json)
		{
			$result = '';
			$pos = 0;
			$strLen = strlen($json);
			$prevChar = '';
			$outOfQuotes = true;

			for ($i=0; $i<=$strLen; $i++) {
				$char = substr($json, $i, 1);
				if ($char == '"' && $prevChar != '\\') {
						$outOfQuotes = !$outOfQuotes;
					} elseif (($char == '}' || $char == ']') && $outOfQuotes) {
						$result .= "\n";
						$pos --;
						for($j=0; $j<$pos; $j++) $result .= "\t";
					}
					$result .= $char;

					if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
						$result .= "\n";
						if($char == '{' || $char == '[') $pos ++;
						for ($j=0; $j<$pos; $j++) $result .= "\t";
					}
				$prevChar = $char;
			}
			return $result;
		}
	}
}
