<?

namespace System
{
	class Http
	{
		private static $headers = array(
			200 => "HTTP/1.1 200 OK",
			301 => "HTTP/1.1 301 Moved Permanently",
			302 => "HTTP/1.1 302 Found",
			303 => "HTTP/1.1 303 See Other",
			307 => "HTTP/1.1 307 Temporary Redirect",
			403 => "HTTP/1.1 403 Forbidden",
			404 => "HTTP/1.1 404 Page Not Found",
			500 => "HTTP/1.1 500 Internal Server Error",
		);


		public static function get_header($num)
		{
			if (isset(self::$headers[$num])) {
				return self::$headers[$num];
			} else throw new \System\Error\Argument(sprintf('Requested http header "%s" does not exist.', $num));
		}
	}
}
