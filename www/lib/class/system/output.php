<?

namespace System
{
	class Output
	{
		const DIR_TEMPLATE = "/lib/template";
		const DIR_PARTIAL = "/lib/template/partial";
		const DEFAULT_TEMPLATE = "pwf/default";
		const DEFAULT_OUT = "html";
		const PREFIX_AJAX = "ajax-api";


		private static $objects = array();
		private static $templates = array();
		private static $def_template_used = false;
		private static $templates_used = array();


		/** Class init
		 * @return void
		 */
		public static function init()
		{
		}


		/** Set output title
		 * @return void
		 */
		public static function set_title()
		{
			self::$title = array();
			foreach (func_get_args() as $title) {
				try { $title = l($title); } catch (\System\Error $e) {}
				self::$title[] = $title;
			}
		}


		/** Get title
		 * @param bool $last
		 * @return string
		 */
		public static function get_title($last = false)
		{
			return $last ?
				end(self::$title):
				implode(' :: ', array_reverse(array_filter(self::$title)));
		}


		/** Set layout template
		 * @param string $temp
		 * @return void
		 */
		public static function set_template($temp)
		{
			self::$template = (array) $temp;
		}


		/** Set output format
		 * @param string $format
		 */
		public static function set_format($format)
		{
			if (!\System\Status::on_cli()) {
				return self::$format = $format;
			}

			self::$format = $format;
		}


		/** Set output options
		 * @param array $opts
		 */
		public static function set_opts(array $opts)
		{
			if (isset($opts['template'])) self::set_template($opts['template']);
			if (isset($opts['format']))   self::set_format($opts['format']);
			if (isset($opts['title']))    self::set_title($opts['title']);
		}


		/** Get output format
		 * @param bool $mime Get mime-type
		 * @return string
		 */
		public static function get_format($format)
		{
			return $format;
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



		public static function count_templates()
		{
			$count = 0;

			foreach (self::$templates as $slot=>$templates) {
				$count += count($templates);
			}

			return $count;
		}


		public static function get_template_data()
		{
			return self::$templates_used;
		}
	}
}
