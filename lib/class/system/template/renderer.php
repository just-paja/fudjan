<?

namespace System\Template
{
	class Renderer extends \System\Model\Attr
	{
		const URL_ICON_PREFIX = '/share/icons';

		protected static $attrs = array(
			"format"               => array('varchar'),
			"start_time"           => array('float'),
			"locales"              => array('object', "model" => '\System\Locales'),
			"request"              => array('object', "model" => '\System\Http\Request'),
			"response"             => array('object', "model" => '\System\Http\Response'),
			"heading_layout_level" => array('int', "default" => 1),
			"heading_level"        => array('int', "default" => null, "is_null" => true),
			"keywords"             => array('varchar'),
			"desc"                 => array('text'),
			"robots"               => array('varchar'),
			"copyright"            => array('varchar'),
			"author"               => array('varchar'),
			"driver"               => array('varchar'),
		);

		private static $meta_tags = array("description", "keywords", "author", "copyright", "robots");
		private static $resource_filter = array('scripts', 'styles');

		private $templates_used = array();
		private $templates = array();
		private $layout;


		/** Create renderer from response
		 * @param \System\Http\Response $response
		 * @return self
		 */
		public static function from_response(\System\Http\Response $response)
		{
			$renderer = new self($response->opts);
			$renderer->format = $response->format;
			$renderer->no_debug = $response->no_debug;

			$renderer->locales  = $response->locales;
			$renderer->request  = $response->request;
			$renderer->response = $response;

			if ($response->render_with) {
				$renderer->driver = $response->render_with;
			} else {
				try {
					$renderer->driver = \System\Settings::get('template', 'renderer');
				} catch (\System\Error\Config $e) {
					$renderer->driver = 'basic';
				}
			}

			if ($response->layout) {
				$renderer->layout = $response->layout;
			}

			return $renderer;
		}


		public function get_driver()
		{
			$namespace = '\System\Template\Renderer\Driver\\';
			$driver = 'Basic';

			if ($this->format == 'html') {
				$driver = ucfirst($this->driver);
			} else {
				$driver = ucfirst($this->format);
			}

			$name = $namespace.$driver;

			if (!class_exists($name)) {
				throw new \System\Error\Config('Could not find template renderer driver.', $driver);
			}

			return new $name(array(
				'renderer' => $this,
				'request'  => $this->request,
				'response' => $this->response,
				'locales'  => $this->locales,
			));
		}


		public function get_layout()
		{
			return $this->layout;
		}


		public function get_slots()
		{
			return $this->templates;
		}


		public function get_context()
		{
			return array_merge(array(
				'flow' => $this->response->flow(),
				'loc'  => $this->response->locales(),
				'ren'  => $this,
				'res'  => $this->response,
				'rq'   => $this->response->request(),
			), $this->response->get_template_context());
		}


		/** Start rendering
		 * @return $this
		 */
		public function render()
		{
			$driver = $this->get_driver();

			$this->response->flush();
			$this->start_time = microtime(true);

			$cont = $driver->render();
			$this->response->set_content($cont);

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


		public function reset_layout()
		{
			$this->layout = array();
			return $this;
		}


		/** Get execution time of rendering. Not returning definite value, since the response will be sent after that.
		 * @return float
		 */
		public function get_exec_time()
		{
			return microtime(true) - $this->start_time;
		}


		private function heading_render($label, $level = null)
		{
			$tag = ($level > 6) ? 'strong':'h'.$level;
			$attrs = array(
				"id"      => \System\Url::gen_seoname($label),
				"content" => $label,
				"output"  => false,
			);

			return \System\Template\Tag::tag($tag, $attrs);
		}


		/** Render heading
		 * @param string   $label
		 * @param int|null $level
		 * @return string
		 */
		public function heading($label, $level = null, $no_increment = false)
		{
			if (is_null($level)) {
				if (is_null($this->heading_level)) {
					$level = $this->heading_layout_level;
				} else {
					$level = $this->heading_level;
				}
			}

			$this->heading_level = $no_increment ? $level:($level + 1);
			return $this->heading_render($label, $level);
		}


		/** Render layout heading
		 * @param string   $label
		 * @param int|null $level
		 * @return string
		 */
		public function heading_layout($label, $level = null, $no_increment = false)
		{
			if (is_null($level)) {
				$level = $this->heading_layout_level;
			}

			$this->heading_layout_level = $no_increment ? $level:($level + 1);
			return $this->heading_render($label, $level);
		}


		/** Render heading without incrementing level
		 * @param string   $label
		 * @param int|null $level
		 */
		public function heading_static($label, $level = null)
		{
			return $this->heading($label, $level, true);
		}


		/**
		 * URL alias for reponse::url
		 *
		 * @param string $name Named route name
		 * @param array  $args Arguments for route
		 * @param int    $var  Variation number of URL
		 * @return string
		 */
		public function url($name, array $args = array(), $var = 0)
		{
			return \System\Router::get_url($this->request->host, $name, $args, $var);
		}


		public function link_resource($type, $name)
		{
			return \System\Resource::get_url('static', $type, $name);
		}


		/**
		 * Return uniform resource locator
		 *
		 * @param string $name Named route name
		 * @param array  $args Arguments for route
		 * @return string
		 */
		public function uri($name, array $args = array(), $var = 0)
		{
			return ($this->request->secure ? 'https':'http').'://'.$this->request->host.$this->url($name, $args, $var);
		}


		public function lang()
		{
			return $this->locales->get_lang();
		}


		public function trans($str)
		{
			$args = func_get_args();
			array_shift($args);
			return $this->locales->trans($str, $args);
		}


		public function format_date($date, $format = 'std', $translate = true)
		{
			return $this->locales->format_date($date, $format, $translate);
		}
	}
}
