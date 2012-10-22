<?

namespace System
{
	class Output
	{
		const PAGE_DIR = "/lib/template";
		const TEMPLATE_DIR = "/lib/template/layout";
		const DEFAULT_TEMPLATE = "default";
		const DEFAULT_OUT = "html";
		const PREFIX_AJAX = "ajax-api";

		private static $format, $lang;
		private static $template = array();
		private static $title = array();
		private static $objects = array();
		private static $templates = array();
		private static $meta = array();
		private static $ajax = false;
		private static $def_template_used = false;
		private static $content = array(
			"headers" => array(),
			"meta" => array(),
			"scripts" => array(),
			"styles" => array(),
			"output" => array()
		);


		/** Class init
		 * @returns void
		 */
		public static function init()
		{
			self::set_title(cfg('default', 'title'));
		}


		/** Set output title
		 * @returns void
		 */
		static function set_title()
		{
			foreach(func_get_args() as $title){
				self::$title[] = $title;
			}
		}


		/** Get title
		 * @param bool $last
		 * @returns string
		 */
		static function get_title($last = false)
		{
			return $last ?
				end(self::$title):
				implode(Settings::get('default', 'title_separator'), array_reverse(self::$title));
		}


		/** Set layout template
		 * @param string $temp
		 * @returns void
		 */
		static function set_template($temp)
		{
			self::$template = (array) $temp;
		}


		static function set_lang($lang)
		{
			if (Settings::get('locales', 'langs', $lang)) {
				Status::log("Locales", array(Settings::get('locales', 'langs', $lang)), true);
				self::$lang = $lang;
			} else {
				Status::log("Locales", array(Settings::get('locales', 'lang', Settings::get('locales', 'default_lang'))."(default)"), true);
				self::$lang = Settings::get('locales', 'default_lang');
			}
		}


		static function get_lang()
		{
			return self::$lang;
		}


		/** Set output format
		 * @param string $format
		 */
		static function set_format($format)
		{
			if ($format == 'cli') {
				return self::$format = $format;
			}

			if (!$format || self::$ajax || (Settings::get('dev', 'debug') && $format == 'xhtml')) {
				$format = self::DEFAULT_OUT;
			}

			Status::log("Output", array(Settings::get('output', 'format', $format)), true);
			self::$format = $format;
		}


		/** Set output options
		 * @param array $opts
		 */
		static function set_opts(array $opts)
		{
			if (isset($opts['template'])) self::set_template($opts['template']);
			if (isset($opts['format']))   self::set_format($opts['format']);
			if (isset($opts['title']))    self::set_title($opts['title']);
			if (isset($opts['lang']))     self::set_lang($opts['lang']);
		}


		/** Get output format
		 * @param bool $mime Get mime-type
		 * @returns string
		 */
		static function get_format($mime = false)
		{
			php_sapi_name() == 'cli' && self::set_format('txt');
			return $mime ? Settings::get('output', 'format', self::$format):self::$format;
		}


		/** Add template into queue
		 * @param array $template
		 * @param string $slot
		 * @returns void
		 */
		static function add_template($template, $slot)
		{
			if (!isset(self::$templates[$slot])) {
				self::$templates[$slot] = array();
			}

			def($template['locals'], array());
			self::$templates[$slot][] = $template;
		}


		/** Output templates in a slot
		 * @param string $name
		 * @returns void
		 */
		static function slot($name = \System\Template::DEFAULT_SLOT)
		{
			if (Settings::get('dev', 'debug')) {
				echo '<!--Slot: "'.$name.'"-->';
			}

			if (isset(self::$templates[$name]) && is_array(self::$templates[$name])) {
				while ($template = array_shift(self::$templates[$name])) {
					if (!empty($template['locals']['heading-level'])) {
						Template::set_heading_level($template['locals']['heading-level']);
						Template::set_heading_section_level($template['locals']['heading-level']);
					}
					Template::partial($template['name'], $template['locals']);
				}
			}
		}


		/** Introduce pwf name and version
		 * @returns string
		 */
		static function introduce()
		{
			return Settings::get('own', 'short_name')." ".Settings::get('own', 'version');
		}


		/** Use ajax api
		 * @param bool $really
		 */
		static function use_ajax($really = true)
		{
			self::$ajax = $really;
			Status::log("out_layout", array("none"));
		}


		/** Get template full path
		 * @param string $type
		 * @param string $name
		 * @param bool $force
		 */
		static function get_template($type = 'layout', $name = null, $force = false)
		{
			$base = ROOT;
			$temp = null;

			switch ($type)
			{
				case 'layout': $base .= self::TEMPLATE_DIR.'/'; break;
				case 'partial': $base .= self::PAGE_DIR.'/partial/'; break;
			}

			file_exists($temp = $base.Template::get_filename($name, self::$format, self::$lang)) ||
			file_exists($temp = $base.Template::get_filename($name, self::$format)) ||
			file_exists($temp = $base.Template::get_filename($name)) ||
			$temp = '';

			$f = false;
			if (empty($temp) && $type != 'page' && $type != 'partial' && !$force) {
				if (
					!self::$def_template_used && (
						file_exists($p = $base.Template::get_filename(self::DEFAULT_TEMPLATE, self::DEFAULT_OUT)) ||
						$p = $base.Template::get_filename(self::DEFAULT_TEMPLATE)
					)
				) {
					$temp = $p;
					self::$def_template_used = true;
					$f = true;
				}
			} elseif (!$temp) {
				$temp = self::get_template('page');
			}

			any($temp) && Status::log("out_template", array(($f===true ? "Forced ".$type." fallback to: ":null).$temp), $f === true ? null:true);
			return $temp;
		}


		/** Include all remaining templates in queue
		 * @returns void
		 */
		public static function yield()
		{
			foreach (self::$template as $name) {
				if (file_exists($f = self::get_template('layout', $name))) {
					include($f);
				} else {
					throw new \MissingFileException("Template not found: ".$f);
				}
			}
		}


		/** Initiate output
		 * @returns void
		 */
		public static function out()
		{
			if (any(self::$objects)) {
				Status::log("out_objects", array("objects in queue: ".count(self::$objects)), any(self::$objects) ? true:false);
			}

			ksort(self::$templates);
			ob_start();

			if (self::$ajax) {
				if (self::$template && ($template = self::get_template('layout', null, true))) {
					include($template);
				} else {
					self::yield();
				}
			} else {

				if (Settings::get('dev', 'debug') && !Output::$ajax) {
					self::add_template(array("name" => 'system/status'), Template::DEFAULT_SLOT);
				}

				$name = array_shift(self::$template);

				is_null($name) ?
					self::slot():
					include(self::get_template('layout', $name));
			}

			self::content_for('output', ob_get_contents());
			ob_end_clean();

			self::send_headers();
			Template::head_out();

			foreach (self::$content['output'] as $row) {
				echo $row;
			}
		}


		/** Send HTTP headers
		 * @returns void
		 */
		public static function send_headers()
		{
			$format = Settings::get('output', 'format', self::$format);
			header("Content-Type: $format;charset=utf-8");
			header("Content-Encoding: gz");

			foreach (self::$content["headers"] as $name => $content) {
				header(ucfirst($name).": ".$content);
			}
		}


		/** Add content into specific place
		 * @param string $place
		 * @param array|string $content
		 * @param bool $overwrite
		 */
		public static function content_for($place, $content, $overwrite = false)
		{
			if (!isset(self::$content[$place]) || $overwrite) {
				self::$content[$place] = $content;
			} else {
				is_array(self::$content[$place]) && self::$content[$place][] = $content;
				is_integer(self::$content[$place]) && self::$content[$place] += $content;
				is_string(self::$content[$place]) && self::$content[$place] .= $content;
			}
		}


		/** Get content from location
		 * @param string $place
		 * @returns string
		 */
		public static function &get_content_from($place)
		{
			return self::$content[$place];
		}


		/** Get content from a location and add it to general output
		 * @param string $place
		 */
		public static function content_from($place)
		{
			self::$content['output'][] = ob_get_contents();

			ob_end_clean();
			self::$content['output'][] = &self::$content[$place];
			ob_start();
		}
	}
}
