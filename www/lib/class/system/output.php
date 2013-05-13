<?

namespace System
{
	class Output
	{
		const DIR_TEMPLATE = "/lib/template";
		const DEFAULT_TEMPLATE = "pwf/default";
		const DEFAULT_OUT = "html";
		const PREFIX_AJAX = "ajax-api";


		/** Class init
		 * @return void
		 */
		public static function init()
		{
		}


		public static function get_mime($format)
		{
			try {
				return cfg('output', 'format', $format);
			} catch (\System\Error $e) {
				return 'unknown mime type';
			}
		}


		/** Introduce pwf name and version
		 * @return string
		 */
		public static function introduce()
		{
			try {
				return cfg('own', 'name')."-".cfg('own', 'version');
			} catch(\System\Error $e) {
				return 'pwf unknown version';
			}
		}
	}
}
