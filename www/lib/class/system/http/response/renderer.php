<?

namespace System\Http\Response
{
	class Renderer extends \System\Model\Attr
	{
		protected static $attrs = array(
			"format" => array('varchar'),
		);

		private $templates_used = array();
		private $templates;
		private $layout;


		public static function from_response(\System\Http\Response $response)
		{
			$renderer = new self(array());

			$data = $response->get_render_data();
			$renderer->format = $response->format;
			$renderer->templates = $data['templates'];
			$renderer->layout = $data['layout'];
			$renderer->response = $response;
			return $renderer;
		}


		public function render()
		{
			try {
				$debug = cfg('dev', 'debug');
			} catch(\System\Error $e) {
				$debug = true;
			}

			if ($debug) {
				$this->partial('system/status');
				$this->response->content_for('styles', 'pwf/elementary');
				$this->response->content_for('styles', 'pwf/devbar');
				$this->response->content_for('scripts', 'lib/jquery');
				$this->response->content_for('scripts', 'pwf');
				$this->response->content_for('scripts', 'pwf/storage');
				$this->response->content_for('scripts', 'pwf/devbar');
			}

			$this->response->flush();
			ob_start();
			empty($this->layout) ? $this->slot():$this->yield();
			$this->response->content_for('output', ob_get_clean());
			$this->render_head();

			return $this;
		}



		/** Initiate output
		 * @return void
		 */
		public function out()
		{

			return implode('', self::$content['output']);
		}


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
				$response = $this->response;

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


		public function render_partial($name, array $locals = array())
		{
			$temp = \System\Template::find($name, \System\Template::TYPE_PARTIAL, $this->format);

			// Convert locals into level on variables
			foreach ((array) $locals as $k=>$v) {
				$k = str_replace('-', '_', $k);
				$$k=$v;
			}

			if (file_exists($temp)) {
				include($temp);
			} else throw new \System\Error\File(sprintf('Partial "%s" not found.', $name));
		}


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

			$this->response->partial($template, $locals, $slot);
		}


		public function render_head()
		{
			$this->render_meta();
			$this->render_title();
			$this->render_scripts();
			$this->render_styles();
		}


		public function render_meta()
		{
			$this->response->content_for("meta", array("name" => 'generator', "content" => \System\Output::introduce()));
			$this->response->content_for("meta", array("http-equiv" => 'content-type', "content" => \System\Output::get_mime($this->format).'; charset=utf-8'));
			$this->response->content_for("meta", array("charset" => 'utf-8'));

			$meta = $this->response->get_content_from("meta");
			foreach ($meta as $name=>$value) {
				if ($value) {
					$this->response->content_for("head", '<meta'.\Tag::html_attrs('meta', $value).'>');
				}
			}
		}


		public function render_title()
		{
			$this->response->content_for("head", \Stag::title(array("content" => $this->response->get_title())));
		}


		public function render_scripts()
		{
			$cont = $this->response->get_content_from("scripts");

			if (!is_null($cont)) {
				$this->response->content_for("head", '<script type="text/javascript" src="/share/scripts/'.$cont.'"></script>');
			}
		}


		public function render_styles()
		{
			$cont = $this->response->get_content_from("styles");

			if (!is_null($cont)) {
				$this->response->content_for("head", '<link type="text/css" rel="stylesheet" href="/share/styles/'.$cont.'" />');
			}
		}

	}
}
