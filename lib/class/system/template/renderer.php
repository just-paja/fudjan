<?

namespace System\Template
{
	class Renderer extends \System\Model\Attr
	{
		protected static $attrs = array(
			"author"     => array('varchar'),
			"copyright"  => array('varchar'),
			"desc"       => array('text'),
			"driver"     => array('varchar'),
			"format"     => array('varchar'),
			"keywords"   => array('varchar'),
			"locales"    => array('object', "model" => '\System\Locales'),
			"request"    => array('object', "model" => '\System\Http\Request'),
			"response"   => array('object', "model" => '\System\Http\Response'),
			"robots"     => array('varchar'),
			"start_time" => array('float'),

			"heading_layout_level" => array('int', "default" => 1),
			"heading_level"        => array('int', "default" => null, "is_null" => true),
		);

		protected static $meta_tags = array(
			'description',
			'keywords',
			'author',
			'copyright',
			'robots',
		);

		protected static $resource_filter = array(
			'scripts' => 'script',
			'styles'  => 'style'
		);

		protected $first_layout = true;
		protected $current_slot = null;
		protected $rendered  = array();
		protected $content   = array();
		protected $layout    = array();
		protected $yield_cnt = 0;

		private $templates_used = array();
		private $templates = array();



		public function flush()
		{
			$this->content = array(
				'title'   => '',
				'meta'    => array(),
				'slots'   => array(),
				'yield'   => array(),
			);

			if ($this->title) {
				$this->content['title'] = $this->title;
			}

			return $this;
		}


		/**
		 * Create renderer from response
		 *
		 * @param \System\Http\Response $response
		 * @return self
		 */
		public static function from_response(\System\Http\Response $res)
		{
			$cname = get_called_class();
			$ren = new $cname($res->opts);
			$ren->format = $res->format;
			$ren->no_debug = $res->no_debug;

			$ren->title    = $res->title;
			$ren->locales  = $res->locales;
			$ren->request  = $res->request;
			$ren->response = $res;

			if ($res->layout) {
				$ren->layout = $res->layout;
			}

			return $ren;
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
				'flow' => $this->flow,
				'loc'  => $this->locales,
				'ren'  => $this,
				'res'  => $this->response,
				'rq'   => $this->request,
				'wrap' => true,
			), $this->response->get_template_context());
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


		public function format_date($date, $format = 'std', $translate = true)
		{
			return $this->locales->format_date($date, $format, $translate);
		}


		/**
		 * Include all remaining templates in queue
		 *
		 * @return void
		 */
		public function render_yield()
		{
			$content = ob_get_contents();

			if ($content) {
				$this->content_for('yield', $content);

				if (ob_get_level()) {
					ob_clean();
				}
			}

			$this->render_layout();
		}


		public function render_layout()
		{
			while ($name = array_shift($this->layout)) {
				$ctx = $this->get_context();

				if ($this->first_layout) {
					$this->first_layout = false;
					$ctx['wrap'] = false;
				}

				$content = $this->render_file($name, $ctx);

				if ($content) {
					$this->content_for('yield', $content);
				}
			}

			return $this;
		}


		/** Start rendering
		 * @return $this
		 */
		public function render()
		{
			$this->response->flush();
			$this->start_time = microtime(true);

			$cont = $this->render_content();
			$this->response->set_content($cont);

			return $this;
		}


		public function render_content()
		{
			$this->flush();
			$this->layout = $this->get_layout();
			$slots = $this->get_slots();

			if ($this->layout) {
				$this->render_layout();
			} else {
				$this->slot();
			}

			foreach ($slots as $slot=>$partials) {
				$this->current_slot = $slot;

				if (isset($this->content['slots'][$slot])) {
					while ($partial = array_shift($partials)) {
						$locals = def($partial['locals'], array());
						$ctx = array_merge($this->get_context(), $locals);
						$ctx['passed'] = &$locals;

						if (any($partial['name'])) {
							$str = null;

							try {
								$str = $this->render_file($partial['name'], $ctx);
							} catch(Exception $e) {
								v($e);
								exit;
							}

							if ($str) {
								$this->content['slots'][$slot][] = $str;
							}
						} else throw new \System\Error\File('Partials must have a name.', $partial);
					}

				} else {
					throw new \System\Error\Config('Rendering partials into non-existent slot!', $slot);
				}
			}


			$this->normalize();
			$this->render_head();
			$out = implode('', $this->content['yield']);

			return $out;
		}


		public function normalize(array &$array = null)
		{
			if (!$array) {
				$array = &$this->content['slots'];
			}

			foreach ($array as $name=>$data) {
				if (is_array($data)) {
					foreach ($data as $key=>$val) {
						$this->normalize($data);
					}

					$array[$name] = implode('', $data);
				}
			}
		}


		public function get_template_path($name)
		{
			return \System\Composer::resolve(\System\Template::DIR_TEMPLATE.'/'.$name.'.'.$this->get_suffix());
		}


		public function render_file($name, array $locals = array())
		{
			$path = $this->get_template_path($name);

			if (!$path) {
				throw new \System\Error\File('Could not find template.', $name, $this->get_suffix(), get_class($this));
			}

			$locals['template'] = str_replace('/', '-', $name);

			try {
				return $this->render_template($path, $locals);
			} catch(\System\Error $e) {
				$exp = $e->get_explanation();

				array_push($exp, 'Error in template');
				array_push($exp, $path);

				$err = new \System\Error\Template();
				$err->set_explanation($exp);

				throw $err;
			}
		}


		/** Get content from a location and add it to general output
		 * @param string $place
		 */
		public function content_from($place)
		{
			if (ob_get_level() > 0) {
				$content = ob_get_contents();

				if ($content) {
					$this->content_for('yield', $content);
				}
			}

			$this->content['yield'][] = &$this->content[$place];

			if (ob_get_level()) {
				ob_clean();
			}
		}


		public function get_yield_name()
		{
			$name = 'yield_'.$this->yield_cnt;

			if (!isset($this->content[$name])) {
				$this->content[$name] = array();
			}

			return $name;
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


		/** Get content from location
		 * @param string $place
		 * @return string
		 */
		public function get_content_from($place)
		{
			if (is_array($this->content[$place]) && array_key_exists($place, self::$resource_filter)) {
				$this->content[$place] = \System\Resource::filter_output_content(self::$resource_filter[$place], $this->content[$place]);
			}

			return $this->content[$place];
		}


		/** Get page metadata from routes and add it into streamed output.
		 * @return $this
		 */
		private function process_meta()
		{
			$dataray = array();

			foreach ((array) self::$meta_tags as $name) {
				if (any($this->data[$name])) {
					$this->content_for('meta', array(
						"name"    => $name,
						"content" => $this->data[$name]
					));
				}
			}

			return $this;
		}


		public function slot_check($name)
		{
			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if (!isset($this->content['slots'][$name])) {
				$this->content['slots'][$name] = array();

				if ($debug && strpos($this->format, 'html') !== false && !\System\Status::on_cli()) {
					$this->content['slots'][$name][] = '<!-- Slot: "'.$name.'" -->';
				}
			}
		}


		/** Output templates in a slot
		 * @param string $name
		 * @return void
		 */
		public function slot($name = \System\Template::DEFAULT_SLOT)
		{
			$this->slot_check($name);

			if (!in_array($name, $this->rendered)) {
				$this->rendered[] = $name;
				$content = null;

				if (ob_get_level() > 0) {
					$content = ob_get_contents();
				}

				$src = &$this->content['slots'][$name];

				if ($this->current_slot) {
					$target = &$this->content['slots'][$this->current_slot];
				} else {
					$target = &$this->content['yield'];
				}

				if ($content) {
					$target[] = $content;
					ob_clean();
				}

				$target[] = &$src;
			}
		}


		public function trans($str)
		{
			$args = func_get_args();
			array_shift($args);
			return $this->locales->trans($str, $args);
		}


		/** Render HTML head
		 * @return $this
		 */
		public function render_head()
		{
			return $this
				->render_meta()
				->render_title()
				->render_frontend_config();
		}


		/** Render HTML meta tag section
		 * @return $this
		 */
		public function render_meta()
		{
			$this->content_for("meta", array("name" => 'generator', "content" => \System\Status::introduce()));
			$this->content_for("meta", array("http-equiv" => 'content-type', "content" => 'text/html; charset=utf-8'));
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


		public function render_frontend_config()
		{
			$conf = $this->request->fconfig;
			$str = json_encode($conf);

			$this->content_for('head', '<script type="text/javascript">var sys = '.$str.'</script>');
			return $this;
		}
	}
}
