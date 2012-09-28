<?

namespace System\Offcom
{
	abstract class Request
	{
		/* Make a get request to URL
		 * @return $content
		 */
		public static function get($url)
		{
			if (function_exists('curl_init')) {
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_USERAGENT, \System\Output::introduce());
				curl_setopt($ch, CURLOPT_HEADER, 1); 
				$content = curl_exec($ch);
				$content = explode("\r\n\r\n", $content, 2);

				$dataray = array(
					"headers"  => $content[0],
					"content" => $content[1],
					"status"  => curl_getinfo($ch, CURLINFO_HTTP_CODE),
				);

				curl_close($ch);
				return new Response($dataray);

			} else throw new \InternalException(l('Please allow CURL extension'));
		}


		static function json($url) {
			return json_decode(self::get($url), true);
		}
	}
}
