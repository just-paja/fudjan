<?

namespace System\Template\Renderer
{
	abstract class Driver extends \System\Model\Attr implements \System\Template\Renderer\Api
	{
		protected static $attrs = array(
			'renderer' => array('object', 'model' => '\System\Template\Renderer'),
		);

		private static $resource_filter = array('scripts', 'styles');
		protected $content = array();
		protected $layout  = array();
		protected $yield   = 0;


		public function get_context()
		{
			return array_merge($this->renderer->get_context(), array(
				'ren'   => $this,
				'trans' => function($str, $rewrite = null) {
					return $this->renderer->trans($str, $rewrite);
				},
				'to_html' => function($obj) {
					return to_html($this->renderer, $obj);
				}
			));
		}


		public function flush()
		{
			$this->content = array(
				"title"   => '',
				"meta"    => array(),
				"styles"  => array(
					'bower/reset-css/reset',
					'styles/pwf/elementary'
				),
				"scripts" => array(
					'bower/jquery/dist/jquery',
					'bower/pwf.js/lib/pwf',
					'bower/pwf-storage/lib/storage',
					'bower/pwf-config/lib/config',
					'bower/pwf-html/lib/html',
				),
				"output"  => array(),
				'slots'   => array(),
				'yield'   => array(),
			);

			return $this;
		}


		/** Include all remaining templates in queue
		 * @return void
		 */
		public function yield()
		{
			$this->content_for('yield', ob_get_clean());
			$this->render_layout();
			ob_start();

		}


		public function render_layout()
		{
			while ($name = array_shift($this->layout)) {
				$this->content_for('yield', $this->render_file($name, $this->get_context()));
			}

			return $this;
		}


		public function render()
		{
			$this->flush();
			$this->layout = $this->renderer->get_layout();
			$slots = $this->renderer->get_slots();

			$this->render_layout();
			$this->render_head();
			$this->content['output'] = $this->content['yield'];

			foreach ($slots as $slot=>$partials) {
				if (isset($this->content['slots'][$slot])) {
					while ($partial = array_shift($partials)) {
						$locals = def($partial['locals'], array());
						$ctx = array_merge($this->get_context(), $locals);
						$ctx['passed'] = &$locals;

						if (any($partial['name'])) {
							try {
								$this->content['slots'][$slot][] = $this->render_file($partial['name'], $ctx);
							} catch(Exception $e) {
								v($e);
								exit;
							}
						} else throw new \System\Error\File('Partials must have a name.', $partial);
					}
				} else {
					throw new \System\Error\Config('Rendering partials into non-existent slot!', $slot);
				}
			}

			foreach ($this->content['slots'] as $slot => $data) {
				$this->content['slots'][$slot] = implode('', $data);
			}

			$out = implode('', $this->content['output']);
			return $out;
		}


		public function get_template_path($name)
		{
			return \System\Composer::resolve(\System\Template::DIR_TEMPLATE.'/'.$name.'.'.$this->get_suffix());
		}


		public function render_file($name, array $locals = array())
		{
			$path = $this->get_template_path($name);

			if ($path) {
				$locals['template'] = str_replace('/', '-', $name);
				return $this->render_template($path, $locals);
			} else throw new \System\Error\File('Could not find template.', $name, $this->get_suffix(), get_class($this));

			return $this;
		}


		/** Get content from a location and add it to general output
		 * @param string $place
		 */
		public function content_from($place)
		{
			$this->content_for('yield', ob_get_level() > 0 ? ob_get_clean():'');
			$this->content['yield'][] = &$this->content[$place];
			ob_start();
		}


		public function get_yield_name()
		{
			$name = 'yield_'.$this->yield;

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
			if (is_array($this->content[$place]) && in_array($place, self::$resource_filter)) {
				$this->content[$place] = \System\Resource::filter_output_content($place, $this->content[$place]);
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


		/** Output templates in a slot
		 * @param string $name
		 * @return void
		 */
		public function slot($name = \System\Template::DEFAULT_SLOT)
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

			$this->content_for('yield', ob_get_level() > 0 ? ob_get_clean():'');
			$this->content['yield'][] = &$this->content['slots'][$name];
			ob_start();
		}


		public function trans($str)
		{
			$args = func_get_args();
			array_shift($args);
			return $this->renderer->response()->locales()->trans($str, $args);
		}


		public function __call($name, $args)
		{
			return call_user_func_array(array($this->renderer, $name), $args);
		}



		/** Render HTML head
		 * @return $this
		 */
		public function render_head()
		{
			return $this->render_meta()->render_title()->render_scripts()->render_styles()->render_frontend_config();
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


		/** Render HTML javascript section
		 * @return $this
		 */
		public function render_scripts()
		{
			$this->content_for('scripts', 'bower/pwf-jquery-compat/lib/jquery-compat');
			$cont = $this->get_content_from("scripts");

			if (!is_null($cont)) {
				$this->content_for("head", '<script type="text/javascript" src="'.$cont.'"></script>');
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
				$this->content_for("head", '<link type="text/css" crossorigin="anonymous" rel="stylesheet" href="'.$cont.'" />');
			}

			return $this;
		}


		public function render_frontend_config()
		{
			try {
				$static_domain = \System\Settings::get('resources', 'domain');
			} catch (\System\Error\Config $e) {
				$static_domain = null;
			}

			try {
				$locales_url = ($static_domain ? '//'.$static_domain:'').$this->url("locale_list");
			} catch (\System\Error\NotFound $e) {
				$locales_url = '';
			}

			try {
				$autoload = \System\Settings::get('locales', 'autoload');
			} catch (\System\Error\Config $e) {
				$autoload = false;
			}

			try {
				$debug = \System\Settings::get('dev', 'debug');
			} catch (\System\Error\Config $e) {
				$debug = array(
					'frontend' => true,
					'backend'  => true
				);
			}

			$cont = array(
				"locales" => array(
					"url"      => substr($locales_url, 0, strlen($locales_url)-1),
					"lang"     => $this->renderer->response()->locales()->get_lang(),
					"autoload" => $autoload,
				),
				"comm" => array(
					"def"   => 'http',
					"blank" => '/share/html/blank.html'
				),
				"debug" => $debug,
				"proxy" => array(
					'url' => '/proxy/head/?url={url}'
				),
			);

			try {
				$frontend = \System\Settings::get('frontend');
			} catch(\System\Error $e) {
				$frontend = array();
			}

			$cont = array_merge_recursive($cont, $frontend);
			$this->content_for('head', '<script type="text/javascript">var sys = '.json_encode($cont).'</script>');
			return $this;
		}


	}
}
