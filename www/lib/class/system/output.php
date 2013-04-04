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

		const TEMPLATE_LAYOUT  = 'layout';
		const TEMPLATE_PARTIAL = 'partial';

		private static $format;
		private static $template = array();
		private static $title = array();
		private static $objects = array();
		private static $templates = array();
		private static $meta = array();
		private static $def_template_used = false;
		private static $content = array(
			"headers" => array(),
			"meta" => array(),
			"scripts" => array(),
			"styles" => array(),
			"output" => array()
		);

		private static $templates_used = array();
		private static $resource_filter = array('scripts', 'styles');


		/** Class init
		 * @return void
		 */
		public static function init()
		{
			self::set_title(cfg('site', 'title'));
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
		public static function get_format($mime = false)
		{
			php_sapi_name() == 'cli' && self::set_format('txt');
			if ($mime) {
				try {
					return cfg('output', 'format', self::$format);
				} catch (\System\Error $e) {
					return 'unknown mime type';
				}
			} else {
				return self::$format;
			}
		}


		/** Add template into queue
		 * @param array $template
		 * @param string $slot
		 * @return void
		 */
		public static function add_template($template, $slot)
		{
			if (!isset(self::$templates[$slot])) {
				self::$templates[$slot] = array();
			}

			def($template['locals'], array());
			self::$templates[$slot][] = $template;
		}


		/** Output templates in a slot
		 * @param string $name
		 * @return void
		 */
		public static function slot($name = \System\Template::DEFAULT_SLOT)
		{
			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if ($debug && !\System\Status::on_cli()) {
				echo '<!--Slot: "'.$name.'"-->';
			}

			if (isset(self::$templates[$name]) && is_array(self::$templates[$name])) {
				while ($template = array_shift(self::$templates[$name])) {
					if (!empty($template['locals']['heading-level'])) {
						Template::set_heading_level($template['locals']['heading-level']);
						Template::set_heading_section_level($template['locals']['heading-level']);
					}

					self::used(self::TEMPLATE_PARTIAL, $template['name'], $template['locals']);
					Template::partial($template['name'], $template['locals']);
				}
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


		/** Get template full path
		 * @param string $type
		 * @param string $name
		 * @param bool $force
		 */
		public static function get_template($type = 'layout', $name = null, $force = false)
		{
			$base = ROOT;
			$temp = null;

			switch ($type)
			{
				case 'layout': $base .= self::DIR_TEMPLATE.'/'; break;
				case 'partial': $base .= self::DIR_PARTIAL.'/'; break;
			}

			file_exists($temp = $base.Template::get_filename($name, self::$format, \System\Locales::get_lang())) ||
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

			return $temp;
		}


		/** Include all remaining templates in queue
		 * @return void
		 */
		public static function yield()
		{
			while (any(self::$template)) {
				$name = array_shift(self::$template);
				self::used(self::TEMPLATE_LAYOUT, $name);

				if (file_exists($f = self::get_template('layout', $name))) {
					include($f);
				} else {
					throw new \System\Error\File(sprintf('Template "%s" not found.', $name));
				}
			}
		}


		/** Initiate output
		 * @return void
		 */
		public static function out()
		{
			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if ($debug) {
				self::add_template(array("name" => 'system/status'), Template::DEFAULT_SLOT);
				content_for('styles', 'pwf/elementary');
				content_for('styles', 'pwf/devbar');
				content_for('scripts', 'lib/jquery');
				content_for('scripts', 'pwf');
				content_for('scripts', 'pwf/storage');
				content_for('scripts', 'pwf/devbar');
			}

			ksort(self::$templates);
			$name = array_shift(self::$template);
			self::used(self::TEMPLATE_LAYOUT, $name);
			self::$content['output'] = array();
			ob_start();

			is_null($name) ?
				self::slot():
				include(self::get_template('layout', $name));

			self::content_for('output', ob_get_clean());

			if (!\System\Status::on_cli()) {
				self::send_headers();
			}

			Template::head_out();

			foreach (self::$content['output'] as $row) {
				echo $row;
			}
		}


		/** Send HTTP headers
		 * @return void
		 */
		public static function send_headers()
		{
			if (!headers_sent()) {
				$format = self::get_format(true);

				foreach (self::$content["headers"] as $name => $content) {
					if (is_numeric($name)) {
						header($content);
					} else {
						header(ucfirst($name).": ".$content);
					}
				}

				header("Content-Type: $format;charset=utf-8");
				header("Content-Encoding: gz");
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
		 * @return string
		 */
		public static function &get_content_from($place)
		{
			if (is_array(self::$content[$place]) && in_array($place, self::$resource_filter)) {
				\System\Resource::filter_output_content($place, self::$content[$place]);
			}

			return self::$content[$place];
		}


		/** Get content from a location and add it to general output
		 * @param string $place
		 */
		public static function content_from($place)
		{
			self::content_for('output', ob_get_clean());
			self::$content['output'][] = &self::$content[$place];
			ob_start();
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


		private static function used($type, $name, $locals = null)
		{
			self::$templates_used[] = array(
				"type"   => $type,
				"name"   => $name,
				"locals" => $locals,
			);
		}
	}
}
