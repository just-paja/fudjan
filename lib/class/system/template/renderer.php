<?

namespace System\Template
{
	class Renderer extends \System\Model\Attr
	{
		const URL_ICON_PREFIX = '/share/icons';

		protected static $attrs = array(
			"format"               => array('varchar'),
			"start_time"           => array('float'),
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
		private $templates;
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
			$renderer->response = $response;

			if ($response->layout) {
				$renderer->layout = $response->layout;
			}

			return $renderer->flush();
		}


		public function construct()
		{
			$this->flush();
		}


		/** Flush output
		 * @return $this
		 */
		public function flush()
		{
			$this->driver = \System\Settings::get('template', 'renderer');
			return $this;
		}


		public function get_driver()
		{
			$driver = '\System\Template\Renderer\Driver\Basic';

			if ($this->format == 'html') {
				$driver = '\System\Template\Renderer\Driver\\'.ucfirst($this->driver);
			}

			if (!class_exists($driver)) {
				throw new \System\Error\Config('Could not find template renderer driver.', $driver);
			}

			return new $driver(array(
				'renderer' => $this
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
				'flow' => $this->response()->flow(),
				'loc'  => $this->response()->locales(),
				'ren'  => $this,
				'res'  => $this->response(),
				'rq'   => $this->response()->request(),
			), $this->response()->get_template_context());
		}


		/** Start rendering
		 * @return $this
		 */
		public function render()
		{
			$driver = $this->get_driver();

			$this->flush();
			$this->response->flush();
			$this->start_time = microtime(true);

			$cont = $driver->render();
			$this->response()->set_content($cont);

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


		/** Render single partial
		 * @param string $name
		 * @param array  $locals Local data for partial
		 * @return void
		 */
		public function render_partial($name, array $locals = array())
		{
			echo $this->render_partial_clean($name, $locals);
		}


		public function render_partial_clean($name, array $locals = array())
		{
			$this->heading_level = $this->heading_layout_level;
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
				$locales  = $this->response()->locales();
				$ren      = &$renderer;

				ob_start();
				include($temp);
				$cont = ob_get_contents();
				ob_end_clean();
				return $cont;
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
		public function response()
		{
			return $this->response;
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


		/** Render link (tag <a/>)
		 * @param string $url    URL to refer to
		 * @param string $label  Label to render with
		 * @param array  $object Additional data (
		 * 	"no-tag" => Render <span/> instead of <a/> if active
		 * 	"strict" => Be strict when checking path - dont count subdirectories
		 * 	 other   => HTML attributes that will be passed to the tag
		 * )
		 * @return string
		 */
		public function link($url, $label, array $object = array())
		{
			def($object['no-tag'], false);
			def($object['strict'], false);
			def($object['class'], array());

			$is_root = false;
			$is_selected = false;

			if (!is_array($object['class'])) {
				$object['class'] = explode(' ', $object['class']);
			}

			if ($url) {
				$is_root = $url == '/' && $this->response()->request()->path == '/';
				$is_selected = $object['strict'] ?
					($url == $this->response()->request()->path || $url == $this->response()->request()->path.'/'):
					(strpos($this->response()->request()->path, $url) === 0);
			}

			if ($is_root || ($url != '/' && $is_selected)) {
				$object['class'][] = 'active';
			}

			if ($object['no-tag'] && $is_selected) {
				$object['class'][] = 'link';
				return span($object['class'], $label);
			} else {
				$object['content'] = $label;
				$object['href'] = $url;
				return \STag::a($object);
			}
		}


		/** Render link (tag <a/>) with reverse url
		 * @param string $url_name  URL that will be reversed
		 * @param string $label    Label to render with
		 * @param array  $object   Aditional data (
		 * 	"args" => Arguments to pass to reverse render
		 * 	other  => same as ::link() function
		 * )
		 */
		public function link_for($url_name, $label, array $object = array())
		{
			def($args, def($object['args'], array()));
			unset($object['args']);
			return $this->link($this->response()->url($url_name, $args), $label, $object);
		}


		public function link_ext($url, $label, array $object = array())
		{
			def($object['class'], array());

			if (!is_array($object['class'])) {
				$object['class'] = explode(' ', $object['class']);
			}

			$object['class'][] = 'ext';
			return $this->link($url, $label, $object);
		}


		/** Render icon
		 * @param string|\System\Image $icon   Icon name ([theme/]type/name) or object
		 * @param string               $size   Icon size, eg '32' or '32x32'
		 * @param array                $object Other attributes passed to icon object
		 * @return string
		 */
		public function icon($icon, $size='32', array $object = array())
		{
			$size_e = explode('x', $size, 2);
			isset($size_e[0]) && $w = $size_e[0];
			isset($size_e[1]) && $h = $size_e[1];
			!isset($h) && $h = $w;

			$icon = $icon instanceof Image ?
				$icon->thumb(intval($w), intval($h), def($object['crop'], false)):
				self::URL_ICON_PREFIX.'/'.$size.'/'.$icon;

			return \Stag::span(array(
				"class" => 'icon isize-'.$size,
				"style" => 'background-image:url('.$icon.'); width:'.$w.'px; height:'.$h.'px',
				"close" => true,
			));
		}


		/** Render icon as link
		 * @param string|\System\Image $icon   Icon name ([theme/]type/name) or object
		 * @param string $url  URL to refer to
		 * @param array  $object Additional data (
		 * 	"size"  => Icon size, default is 32
		 * 	other   => HTML attributes that will be passed to the link tag
		 * )
		 * @return string
		 */
		public function icon_for($url, $icon, $size='32', array $object = array())
		{
			return $this->link($url, $this->icon($icon, $size), $object);
		}


		/** Label with icon as link
		 * @param string              $url
		 * @param string              $label
		 * @param string|System\Image $icon   Icon name ([theme/]type/name) or object
		 * @param string              $size
		 * @param array               $object
		 * @return string
		 */
		public function label_for($url, $label, $icon, $size='32', array $object = array())
		{
			$html = array($this->icon($icon, $size), span('label', $label));

			if (def($object['label_left'], false)) {
				$html = array_reverse($html);
			}

			return $this->link($url, $html, $object);
		}


		/** Label with icon as link using reverse urls
		 * @param string              $url
		 * @param string              $label
		 * @param string|System\Image $icon   Icon name ([theme/]type/name) or object
		 * @param string              $size
		 * @param array               $object
		 * @return string
		 */
		public function label_for_url($url, $label, $icon, $size='32', array $object = array())
		{
			def($args, def($object['args'], array()));
			unset($object['args']);

			return $this->label_for($this->url($url, $args), $label, $icon, $size, $object);
		}


		/** Icon as link using reverse urls
		 * @param string              $url
		 * @param string              $label
		 * @param string|System\Image $icon   Icon name ([theme/]type/name) or object
		 * @param string              $size
		 * @param array               $object
		 * @return string
		 */
		public function icon_for_url($url, $icon, $size='32', array $object = array())
		{
			def($args, def($object['args'], array()));
			unset($object['args']);

			return $this->icon_for($this->url($url, $args), $icon, $size, $object);
		}


		/** URL alias for reponse::url
		 * @param string $name Named route name
		 * @param array  $args Arguments for route
		 * @param int    $var  Variation number of URL
		 * @return string
		 */
		public function url($name, array $args = array(), $var = 0)
		{
			return \System\Router::get_url($this->response()->request()->host, $name, $args, $var);
		}


		/** Return uniform resource locator
		 * @param string $name Named route name
		 * @param array  $args Arguments for route
		 * @return string
		 */
		public function uri($name, array $args = array(), $var = 0)
		{
			$rq = $this->response()->request();
			return ($rq->secure ? 'https':'http').'://'.$rq->host.$this->url($name, $args, $var);
		}


		/** Label with icon as link, name on left
		 * @param string              $url
		 * @param string              $label
		 * @param string|System\Image $icon   Icon name ([theme/]type/name) or object
		 * @param string              $size
		 * @param array               $object
		 * @return string
		 */
		public function label_for_left($url, $label, $icon, $size='32', array $object = array())
		{
			$object['label_left'] = true;
			return $this->label_for($url, $label, $icon, $size, $object);
		}



		/** Create form object from this renderer
		 * @param array $attrs
		 * @return \System\Form
		 */
		public function form(array $attrs = array())
		{
			return \System\Form::from_renderer($this, $attrs);
		}


		/** Create form object including object info from this renderer
		 * @param array $info
		 * @return \System\Form
		 */
		public function form_checker(array $data)
		{
			$f = $this->form($data);

			foreach ($data['info'] as $i=>$text) {
				$f->text($i, $text);
			}

			$f->submit(isset($data['submit']) ? $data['submit']:$this->locales()->trans('delete'));
			return $f;
		}


		public function locales()
		{
			return $this->response()->locales();
		}


		public function lang()
		{
			return $this->response()->locales()->get_lang();
		}


		public function trans($str)
		{
			$args = func_get_args();
			array_shift($args);
			return $this->response()->locales()->trans($str, $args);
		}


		public function format_date($date, $format = 'std', $translate = true)
		{
			return $this->response()->locales()->format_date($date, $format, $translate);
		}
	}
}
