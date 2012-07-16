<?

namespace System
{
	class Json
	{
		/** Read all JSON files in dir and return content
		 * @param string $dir_dist Path to directory
		 * @param &array $temp     Variable to write in
		 * @return array
		 */
		public static function read_dist($dir_dist, &$temp = array(), $assoc_keys = false)
		{
			!$assoc_keys && ($temp = array());
			$dir = opendir($dir_dist);
			while ($f = readdir($dir)) {
				if (strpos($f, ".") !== 0 && strpos($f, ".json")) {
					list($mod) = explode(".", $f);
					$json = (array) json_decode(file_get_contents($dir_dist.'/'.$f), true);

					if ($assoc_keys) {
						$temp[$mod] = $json;
					} else $temp = array_merge_recursive($temp, $json);
				}
			}
			closedir($dir);
			return $temp;
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
