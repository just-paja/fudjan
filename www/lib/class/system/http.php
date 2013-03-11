<?

namespace System
{
	class Http
	{
		const OK                    = 200;
		const MOVED_PERMANENTLY     = 301;
		const FOUND                 = 302;
		const SEE_OTHER             = 303;
		const TEMPORARY_REDIRECT    = 307;
		const FORBIDDEN             = 403;
		const PAGE_NOT_FOUND        = 404;
		const INTERNAL_SERVER_ERROR = 500;


		private static $headers = array(
			self::OK                    => "HTTP/1.1 200 OK",
			self::MOVED_PERMANENTLY     => "HTTP/1.1 301 Moved Permanently",
			self::FOUND                 => "HTTP/1.1 302 Found",
			self::SEE_OTHER             => "HTTP/1.1 303 See Other",
			self::TEMPORARY_REDIRECT    => "HTTP/1.1 307 Temporary Redirect",
			self::FORBIDDEN             => "HTTP/1.1 403 Forbidden",
			self::PAGE_NOT_FOUND        => "HTTP/1.1 404 Page Not Found",
			self::INTERNAL_SERVER_ERROR => "HTTP/1.1 500 Internal Server Error",
		);


		public static function get_header($num)
		{
			if (isset(self::$headers[$num])) {
				return self::$headers[$num];
			} else throw new \System\Error\Argument(sprintf('Requested http header "%s" does not exist.', $num));
		}


		public static function redirect($url, $code=self::TEMPORARY_REDIRECT)
		{
			if (!\System\Status::on_cli()) {
				self::save_referer();
				session_write_close();

				header(\System\Http::get_header($code));
				header("Location: ".$url);
			} else throw new \System\Error\Format(stprintf('Cannot redirect to "%s" while on console.', $r['url']));

			exit(0);
		}


		public static function save_referer()
		{
			$_SESSION['referer'] = $_SERVER['REQUEST_URI'];
		}
	}
}
