<?

namespace System\Template
{
	class Renderer extends \System\Model\Attr
	{
		protected static $attrs = array(
			"format"     => array('varchar'),
			"start_time" => array('float'),
			"response"   => array('object', "model" => '\System\Http\Response'),
		);

		private static $resource_filter = array('scripts', 'styles');
		private $templates_used = array();
		private $templates;
		private $layout;
		private $content;


		/** Create renderer from response
		 * @param \System\Http\Response $response
		 * @return self
		 */
		public static function from_response(\System\Http\Response $response)
		{
			$renderer = new self(array(
				"response" => $response,
				"format"   => $response->format
			));

			if ($response->page) {
				$renderer->layout = $response->page->layout;

				foreach ($response->page->get_meta() as $meta) {
					$this->content_for("meta", $meta);
				}
			}

			$renderer->flush();
			return $renderer;
		}


		/** Flush output
		 * @return $this
		 */
		public function flush()
		{
			$this->content = array(
				"title"   => '',
				"meta"    => array(),
				"styles"  => array(),
				"scripts" => array(),
				"output"  => array(),
			);

			return $this;
		}


		/** Start rendering
		 * @return $this
		 */
		public function render()
		{
			$this->start_time = microtime(true);

			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if ($debug && !$this->response->no_debug) {
				$this->partial('system/status');
				$this->content_for('styles', 'pwf/elementary');
				$this->content_for('styles', 'pwf/devbar');
				$this->content_for('scripts', 'lib/jquery');
				$this->content_for('scripts', 'lib/functions');
				$this->content_for('scripts', 'pwf');
				$this->content_for('scripts', 'pwf/storage');
				$this->content_for('scripts', 'pwf/devbar');
			}

			$this->response->flush();
			ob_start();
			empty($this->layout) ? $this->slot():$this->yield();
			$this->content_for('output', ob_get_clean());
			$this->render_head();

			$this->response()->set_content(implode('', $this->content['output']));
			$this->flush();
			return $this;
		}



		/** Initiate output
		 * @return void
		 */
		public function out()
		{

			return implode('', self::$content['output']);
		}


		/** Mark template as used and save locals for debug purposes
		 * @param string $type
		 * @param string $name
		 * @param array $locals
		 */
		private function used($type, $name, $locals = null)
		{
			$this->templates_used[] = array(
				"type"   => $type,
				"name"   => $name,
				"locals" => $locals,
			);
		}


		/** Include all remaining templates in queue
		 * @return void
		 */
		public function yield()
		{
			while (any($this->layout)) {
				$name = array_shift($this->layout);
				$this->used(\System\Template::TYPE_LAYOUT, $name);

				$renderer = $this;
				$response = $this->response();
				$request  = $this->response()->request();

				if (file_exists($f = \System\Template::find($name, \System\Template::TYPE_LAYOUT, $this->format))) {
					include($f);
				} else throw new \System\Error\File(sprintf('Template "%s" not found.', $name));
			}
		}


		/** Output templates in a slot
		 * @param string $name
		 * @return void
		 */
		public function slot($name = \System\Template::DEFAULT_SLOT)
		{
			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if ($debug && !\System\Status::on_cli()) {
				echo '<!--Slot: "'.$name.'"-->';
			}

			if (isset($this->templates[$name]) && is_array($this->templates[$name])) {
				while ($template = array_shift($this->templates[$name])) {
					if (!empty($template['locals']['heading-level'])) {
						\System\Template::set_heading_level($template['locals']['heading-level']);
						\System\Template::set_heading_section_level($template['locals']['heading-level']);
					}

					$this->used(\System\Template::TYPE_PARTIAL, $template['name'], $template['locals']);
					$this->render_partial($template['name'], $template['locals']);
				}
			}
		}


		/** Render single partial
		 * @param string $name
		 * @param array  $locals Local data for partial
		 * @return void
		 */
		public function render_partial($name, array $locals = array())
		{
			$temp = \System\Template::find($name, \System\Template::TYPE_PARTIAL, $this->format);

			// Convert locals into level on variables
			foreach ((array) $locals as $k=>$v) {
				$k = str_replace('-', '_', $k);
				$$k=$v;
			}

			if (file_exists($temp)) {
				$renderer = $this;
				$response = $this->response();
				$flow     = $this->response()->flow();
				$request  = $this->response()->request();

				include($temp);
			} else throw new \System\Error\File(sprintf('Partial "%s" not found.', $name));
		}


		/** Add default system resources
		 * @return void
		 */
		public function add_default_resources()
		{
			$this->content_for('scripts', 'lib/functions');
			$this->content_for('scripts', 'lib/jquery');
			$this->content_for('scripts', 'lib/browser');
			$this->content_for('scripts', 'pwf');
			$this->content_for('scripts', 'pwf/storage');
			$this->content_for('styles', 'pwf/elementary');
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


		/** Render HTML head
		 * @return $this
		 */
		public function render_head()
		{
			return $this->render_meta()->render_title()->render_scripts()->render_styles();
		}


		/** Render HTML meta tag section
		 * @return $this
		 */
		public function render_meta()
		{
			$this->content_for("meta", array("name" => 'generator', "content" => \System\Output::introduce()));
			$this->content_for("meta", array("http-equiv" => 'content-type', "content" => \System\Output::get_mime($this->format).'; charset=utf-8'));
			$this->content_for("meta", array("charset" => 'utf-8'));

			$meta = $this->get_content_from("meta");

			foreach ($meta as $name=>$value) {
				if ($value) {
					$this->content_for("head", '<meta'.\Tag::html_attrs('meta', $value).'>');
				}
			}

			return $this;
		}


		/** Render HTML title section
		 * @return $this
		 */
		public function render_title()
		{
			$this->content_for("head", \Stag::title(array("content" => $this->get_content_from('title'))));
			return $this;
		}


		/** Render HTML javascript section
		 * @return $this
		 */
		public function render_scripts()
		{
			$cont = $this->get_content_from("scripts");

			if (!is_null($cont)) {
				$this->content_for("head", '<script type="text/javascript" src="/share/scripts/'.$cont.'"></script>');
			}

			return $this;
		}


		/** Render HTML css style section
		 * @return $this
		 */
		public function render_styles()
		{
			$cont = $this->get_content_from("styles");

			if (!is_null($cont)) {
				$this->content_for("head", '<link type="text/css" rel="stylesheet" href="/share/styles/'.$cont.'" />');
			}

			return $this;
		}


		public function response()
		{
			return $this->response;
		}


		/** Get execution time of rendering. Not returning definite value, since the response will be sent after that.
		 * @return float
		 */
		public function get_exec_time()
		{
			return microtime(true) - $this->start_time;
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
	}
}
