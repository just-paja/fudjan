<?

namespace System
{
	class Output
	{
		const FORMAT_HTML  = 'html';
		const FORMAT_XHTML = 'xhtml';
		const FORMAT_XML   = 'xml';
		const FORMAT_JSON  = 'json';
		const FORMAT_TXT   = 'txt';

		private static $mime = array(
			self::FORMAT_HTML  => "text/html",
			self::FORMAT_XHTML => "application/xhtml+xml",
			self::FORMAT_XML   => "application/xml",
			self::FORMAT_JSON  => "application/json",
			self::FORMAT_TXT   => "text/plain",
		);

		const DIR_TEMPLATE = "/lib/template";
		const DEFAULT_TEMPLATE = "pwf/default";
		const DEFAULT_OUT = "html";
		const PREFIX_AJAX = "ajax-api";


		public static function get_mime($format)
		{
			if (isset(self::$mime[$format])) {
				return self::$mime[$format];
			} else throw new \System\Error\Argument(sprintf("Unknown mime type format '%s'.", $format));
		}
	}
}
