<?

namespace System\Http
{
	class Response extends \System\Model\Attr
	{
		protected static $attrs = array(
			"format"   => array('varchar'),
			"lang"     => array('varchar'),
			"title"    => array('varchar'),
			"layout"   => array('array'),
			"no_debug" => array('bool'),
		);

		private static $resource_filter = array('scripts', 'styles');
		private $page;
		private $templates = array();
		private $layout    = array();
		private $request;
		private $renderer;
		private $content = array(
			"headers" => array(),
			"meta" => array(),
			"scripts" => array(),
			"styles" => array(),
			"output" => array()
		);


		public static function from_request(\System\Http\Request $request)
		{
			$response = new self(array(
				"format" => cfg("output", 'format_default'),
				"lang"   => \System\Locales::get_lang(),
			));

			$response->request = $request;
			return $response;
		}


		public static function from_page(\System\Http\Request $request, \System\Page $page)
		{
			foreach ($page->get_meta() as $meta) {
				content_for("meta", $meta);
			}

			$response = self::from_request($request);
			$response->update_attrs($page->get_data());
			$response->page = $page;
			$response->title = $page->title;
			$response->layout = $page->layout;

			if ($request->cli) {
				$response->format = 'txt';
			}

			return $response;
		}


		public function render()
		{
			$this->renderer = $this->get_renderer()->render();
			return $this;
		}


		public function get_renderer()
		{
			if (!$this->renderer) {
				$this->renderer = \System\Http\Response\Renderer::from_response($this);
			}

			return $this->renderer;
		}


		/** Send HTTP headers
		 * @return void
		 */
		public function send_headers()
		{
			if (!\System\Status::on_cli()) {
				$format = \System\Output::get_mime(true);

				foreach ($this->content["headers"] as $name => $content) {
					if (is_numeric($name)) {
						header($content);
					} else {
						header(ucfirst($name).": ".$content);
					}
				}

				header("Content-Type: $format;charset=utf-8");
				header("Content-Encoding: gz");
			}

			return $this;
		}


		public function display()
		{
			echo implode('', $this->content['output']);
		}


		/** Add template into queue
		 * @param string $template
		 * @param string $slot
		 * @return void
		 */
		public function partial($template, array $locals = array(), $slot = \System\Template::DEFAULT_SLOT)
		{
			if (!isset($this->templates[$slot])) {
				$this->templates[$slot] = array();
			}

			$this->templates[$slot][] = array(
				"name"   => $template,
				"locals" => $locals,
			);
		}



		/** Get content from location
		 * @param string $place
		 * @return string
		 */
		public function &get_content_from($place)
		{
			if (is_array($this->content[$place]) && in_array($place, self::$resource_filter)) {
				\System\Resource::filter_output_content($place, $this->content[$place]);
			}

			return $this->content[$place];
		}


		/** Get content from a location and add it to general output
		 * @param string $place
		 */
		public function content_from($place)
		{
			$this->content_for('output', ob_get_clean());
			$this->content['output'][] = &$this->content[$place];
			ob_start();
		}


		/** Add content into specific place
		 * @param string $place
		 * @param array|string $content
		 * @param bool $overwrite
		 */
		public function content_for($place, $content, $overwrite = false)
		{
			if (!isset($this->content[$place]) || $overwrite) {
				$this->content[$place] = $content;
			} else {
				is_array($this->content[$place]) && $this->content[$place][] = $content;
				is_integer($this->content[$place]) && $this->content[$place] += $content;
				is_string($this->content[$place]) && $this->content[$place] .= $content;
			}
		}


		public function flush()
		{
			$this->content['output'] = array();
		}


		public function get_render_data()
		{
			return array(
				"templates" => $this->templates,
				"layout"    => $this->layout,
			);
		}


		public function get_title()
		{
			return $this->title;
		}
	}
}



