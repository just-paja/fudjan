<?

namespace System\Template\Renderer
{
	abstract class Driver extends \System\Model\Attr implements \System\Template\Renderer\Api
	{
		protected static $attrs = array(
			'renderer' => array('object', 'model' => '\System\Template\Renderer'),
			'request'  => array('object', 'model' => '\System\Http\Request'),
			'response' => array('object', 'model' => '\System\Http\Response'),
		);

		private static $resource_filter = array(
			'scripts' => 'script',
			'styles'  => 'style'
		);

		private $first_layout = true;

		protected $current_slot = null;
		protected $rendered  = array();
		protected $content   = array();
		protected $layout    = array();
		protected $yield_cnt = 0;


		public function get_context()
		{
			return array_merge(array('wrap' => true), $this->renderer->get_context(), array(
				'ren' => $this
			));
		}


		public function flush()
		{
			$this->content = array(
				'title'   => '',
				'meta'    => array(),
				'slots'   => array(),
				'yield'   => array(),
			);

			return $this;
		}


		/** Include all remaining templates in queue
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


		public function render()
		{
			$this->flush();
			$this->layout = $this->renderer->get_layout();
			$slots = $this->renderer->get_slots();

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


//~ v($this->content);
//~ exit;

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


		public function __call($name, $args)
		{
			$call = array($this->renderer, $name);

			if (is_callable($call)) {
				return call_user_func_array($call, $args);
			} else throw new \System\Error\Template('Tried to call undefined method', $name);
		}



		/** Render HTML head
		 * @return $this
		 */
		public function render_head()
		{
			return $this
				->render_meta()
				->render_title()
				//~ ->render_scripts()
				//~ ->render_styles()
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
			$conf = $this->renderer->response->request->fconfig;
			$str = json_encode($conf);

			$this->content_for('head', '<script type="text/javascript">var sys = '.$str.'</script>');
			return $this;
		}
	}
}
