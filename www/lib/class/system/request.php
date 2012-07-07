<?

namespace System
{
	abstract class Request
	{
		/* Make a get request to URL
		 * @return $content
		 */
		public static function get($url)
		{
			if (ini_get('allow_url_fopen') == '1') {
				$content = file_get_contents($url); 
			} elseif(function_exists('curl_init')) {
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_HEADER, 0); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_USERAGENT, \System\Output::introduce());
				$content = curl_exec($ch);
				curl_close($ch);
			}

			return $content;
		}


		static function json($url) {
			return json_decode(self::get($url), true);
		}
	}
}
