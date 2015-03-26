<?

namespace System\Http
{
	class Response extends \System\Model\Attr
	{
		const NO_RESPONSE           = 0;
		const OK                    = 200;
		const NO_CONTENT            = 204;
		const MOVED_PERMANENTLY     = 301;
		const FOUND                 = 302;
		const SEE_OTHER             = 303;
		const TEMPORARY_REDIRECT    = 307;
		const BAD_REQUEST           = 400;
		const FORBIDDEN             = 403;
		const PAGE_NOT_FOUND        = 404;
		const INTERNAL_SERVER_ERROR = 500;


		private static $states = array(
			self::OK                    => "HTTP/1.1 200 OK",
			self::NO_CONTENT            => "HTTP/1.1 204 No Content",
			self::MOVED_PERMANENTLY     => "HTTP/1.1 301 Moved Permanently",
			self::FOUND                 => "HTTP/1.1 302 Found",
			self::SEE_OTHER             => "HTTP/1.1 303 See Other",
			self::TEMPORARY_REDIRECT    => "HTTP/1.1 307 Temporary Redirect",
			self::BAD_REQUEST           => "HTTP/1.1 400 Bad Request",
			self::FORBIDDEN             => "HTTP/1.1 403 Forbidden",
			self::PAGE_NOT_FOUND        => "HTTP/1.1 404 Page Not Found",
			self::INTERNAL_SERVER_ERROR => "HTTP/1.1 500 Internal Server Error",
		);

		protected static $attrs = array(
			"format"        => array("type" => 'varchar'),
			"no_debug"      => array("type" => 'bool'),
			"title"         => array("type" => 'varchar'),
			"flow"          => array("type" => 'object', "model" => '\System\Module\Flow'),
			"groups"        => array("type" => 'list'),
			"locales"       => array("type" => 'object', "model" => '\System\Locales'),
			"modules"       => array("type" => 'list'),
			"init"          => array("type" => 'list'),
			"policies"      => array("type" => 'list'),
			"context"       => array("type" => 'list'),
			"layout"        => array("type" => 'list'),
			"request"       => array("type" => 'object', "model" => '\System\Http\Request'),
			"renderer"      => array("type" => 'object', "model" => '\System\Template\Renderer'),
			"render_with"   => array("type" => 'varchar', "is_null" => true),
			"skip_render"   => array("type" => 'bool', 'default' => false),
			"start_time"    => array("type" => 'float'),
			"sent"          => array("type" => 'bool'),
			"pass"          => array("type" => 'bool', 'default' => true),
		);

		private $status    = self::OK;
		private $headers   = array();
		private $content   = null;
		private $init_done = false;
		private $ctx_data  = array();


		/** Get response HTTP status
		 * @param int $num
		 * @return String
		 */
		public static function get_status($num)
		{
			if (isset(self::$states[$num])) {
				return self::$states[$num];
			} else throw new \System\Error\Argument(sprintf('Requested http header "%s" does not exist.', $num));
		}


		/** Redirect immediately page to another URL
		 * @param string $url
		 * @param int    $code
		 * @return void
		 */
		public static function redirect($url, $code = self::FOUND)
		{
			if (!\System\Status::on_cli()) {
				session_write_close();

				header(self::get_status($code));
				header("Location: ".$url);
			} else throw new \System\Error\Format(stprintf('Cannot redirect to "%s" while on console.', $r['url']));

			exit(0);
		}


		/** Create response from request
		 * @param \System\Http\Request $request
		 * @return self
		 */
		public static function from_request(\System\Http\Request $rq, array $attrs = array())
		{
			def($attrs['format'], null);
			def($attrs['start_time'], microtime(true));

			if (empty($attrs['format'])) {
				try {
					$attrs['format'] = \System\Settings::get("output", 'format_default');
				} catch (\System\Error $e) {
					$attrs['format'] = 'html';
				}
			}

			if (!isset($attrs['layout'])) {
				$attrs['layout'] = $rq->layout;
			}

			$res = new self($attrs);

			$res->data['request'] = $rq;
			$res->data['locales'] = \System\Locales::create($res, $rq->lang)->make_syswide();

			return $res;
		}


		public function create_flow()
		{
			$this->data['flow'] = new \System\Module\Flow($this, $this->modules);
			return $this;
		}


		/** Execute modules
		 * @return $this
		 */
		public function exec()
		{
			return $this
				->create_renderer()
				->exec_lld()
				->exec_policies()
				->exec_flow()
				->exec_context_processors();
		}


		public function exec_policies()
		{
			$list = $this->get_policies();

			foreach ($list as $policy) {
				$this->use_policy_file($policy);

				if (!$this->pass) {
					break;
				}
			}

			return $this;
		}


		private function use_policy_file($path)
		{
			require($path);

			if (isset($policy) && get_class($policy) == 'Closure') {
				$this->pass = $policy($this->request, $this);
			} else throw new \System\Error\Code('Failed to use policy. Maybe you forgot define variable policy as function.', $path);

			return $this;
		}


		public function get_policies()
		{
			$list  = $this->request->policies;
			$files = array();

			if ($this->policies) {
				$list = $this->policies;
			}

			foreach ($list as $set) {
				try {
					$names = cfg('policies', $set);
				} catch(\System\Error\Config $e) {
					$names = array($set);
				}

				foreach ($names as $file) {
					$file_path = \System\Composer::resolve('/lib/policy/'.$file.'.php');

					if ($file_path) {
						array_push($files, $file_path);
					} else throw new \System\Error\Config('Failed to load policy file.', $file);
				}
			}

			return $files;
		}


		public function exec_flow()
		{
			if ($this->pass) {
				$this->flow()->exec();
			}

			return $this;
		}


		public function exec_context_processors()
		{
			$list = $this->get_context_processors();

			foreach ($list as $ctx) {
				$this->use_context_file($ctx);
			}

			return $this;
		}


		private function use_context_file($path)
		{
			require($path);
			$data = array();

			if (isset($context)) {
				if (get_class($context) == 'Closure') {
					$data = $context($this->request, $this);
				} else {
					$data = (array) $context;
				}
			} else throw new \System\Error\Code('Failed to use context processor. Maybe you forgot to define context variable.', $path);

			$this->ctx_data = array_merge($this->ctx_data, (array) $data);
			return $this;
		}


		public function get_context_processors()
		{
			$list  = !!$this->context ? $this->context:$this->request->context;
			$files = array();

			foreach ($list as $name) {
				$file_path = \System\Composer::resolve('/lib/context/'.$name.'.php');

				if ($file_path) {
					array_push($files, $file_path);
				} else throw new \System\Error\Config('Failed to find context processor.', $name);
			}

			return $files;
		}


		public function get_template_context()
		{
			return $this->ctx_data;
		}


		/** Render response content
		 * @return $this
		 */
		public function render()
		{
			if (!$this->skip_render) {
				$this->renderer->render();
			}

			return $this;
		}


		public function send()
		{
			$this->send_headers()->send_content();
			$this->sent = true;
			return $this;
		}


		/** Send HTTP headers
		 * @return void
		 */
		public function send_headers()
		{
			if (!\System\Status::on_cli()) {
				session_write_close();

				def($this->headers['Content-Encoding'], 'gz');
				def($this->headers['Generator'], 'pwf');

				if (!isset($this->headers['Content-Type'])) {
					if ($this->mime) {
						$mime = $this->mime;
					} else {
						try {
							$mime = \System\Output::get_mime($this->renderer->format);
						} catch(\System\Error\Argument $e) {
							$mime = 'text/html; charset=utf-8';
						}
					}

					def($this->headers["Content-Type"], $mime.";charset=utf-8");
				}

				if (isset($this->size)) {
					def($this->headers['Content-Length'], $this->size);
				}

				if ($this->status == self::OK && empty($this->content)) {
					$this->status(self::NO_CONTENT);
				}

				header(self::get_status($this->status));

				foreach ($this->headers as $name => $content) {
					if (is_numeric($name)) {
						header($content);
					} else {
						$name = implode('-', array_map('ucfirst', explode('-', $name)));
						header($name.": ".$content);
					}
				}
			}

			return $this;
		}


		public function header($name, $value)
		{
			$this->headers[$name] = $value;
			return $this;
		}


		/** Send response content to output
		 * @return $this
		 */
		public function send_content()
		{
			echo $this->content;
			return $this;
		}


		public function get_renderer_driver()
		{
			$namespace = '\System\Template\Renderer\\';
			$driver = 'Basic';

			if ($this->render_with) {
				$driver = $this->render_with;
			} else {
				try {
					$driver = \System\Settings::get('template', 'renderer');
				} catch (\System\Error\Config $e) {
					$driver = 'basic';
				}
			}

			if ($this->format == 'html') {
				$driver = ucfirst($driver);
			} else {
				$driver = ucfirst($this->format);
			}

			return $namespace.$driver;
		}


		/** Get renderer object
		 * @return \System\Template\Renderer
		 */
		public function create_renderer()
		{
			if (!$this->renderer) {
				$name = $this->get_renderer_driver();

				if (class_exists($name)) {
					$this->renderer = $name::from_response($this);
				} else {
					throw new \System\Error\Template('Renderer driver was not found', $name);
				}
			}

			return $this;
		}


		public function reset_renderer()
		{
			$this->renderer = null;
			return $this->create_renderer();
		}


		/** Clear response content
		 * @return $this
		 */
		public function flush()
		{
			$this->content = array('output' => array());
			return $this;
		}


		/** Get flow object
		 * @return \System\Module\Flow
		 */
		public function flow()
		{
			return $this->flow;
		}


		/** Create form object from this renderer
		 * @param array $attrs
		 * @return \System\Form
		 */
		public function form(array $attrs = array())
		{
			return \System\Form::from_response($this, $attrs);
		}


		/** Get full path including query string
		 * @return string
		 */
		public function path()
		{
			return $this->request->path.($this->request->query ? '?'.$this->request->query:'');
		}


		/** Get execution time of rendering. Not returning definite value, since the response will be sent after that.
		 * @return float
		 */
		public function get_exec_time()
		{
			return microtime(true) - $this->start_time;
		}


		/** Set response HTTP status
		 * @param int $status
		 * @return void
		 */
		public function status($status)
		{
			if (isset(self::$states[$status])) {
				$this->status = $status;
			} else throw new \System\Error\Argument(sprintf("HTTP status '%s' was not found.", $status));
		}


		/** Set response content
		 * @param string $content
		 * @return $this
		 */
		public function set_content($content)
		{
			if (is_string($content)) {
				$this->content = $content;
				return $this;
			} else throw new \System\Error\Argument(sprintf("HTTP Response must be string! '%s' given.", gettype($content)));
		}


		public function cookie_store($name, $val = null, array $params = array())
		{
			def($params['expire'], 0);
			def($params['path'], null);
			def($params['domain'], null);
			def($params['secure'], false);
			def($params['httponly'], false);

			setcookie($name, $val, $params['expire'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}


		public function session_store($name, $val = null)
		{
			if ($val === null) {
				unset($_SESSION[$name]);
			} else {
				$_SESSION[$name] = $val;
			}
		}


		/** Run low level debug - Include a PHP file just after init and before module flow
		 * @return void
		 */
		public function exec_lld()
		{
			if (!$this->no_debug && \System\Settings::get('dev', 'debug', 'backend')) {
				if (file_exists(ROOT.'/lib/include/devel.php')) {
					$response = $this;
					$request  = $this->request;
					$renderer = $this->renderer;
					$locales  = $this->locales;
					$flow     = $this->flow;
					$ren      = &$renderer;

					include ROOT.'/lib/include/devel.php';
				}
			}

			return $this;
		}


		/**
		 * Shortcut for get_url
		 *
		 * @param String $name
		 * @param array  $args
		 * @param int    $variation
		 * @return String
		 */
		public function url($name, array $args = array(), $variation = 0)
		{
			return \System\Router::get_url($this->request->host, $name, $args, $variation);
		}


		/**
		 * Gets you full URL
		 *
		 * @param String $name
		 * @param array  $args
		 * @param int    $variation
		 * @return String
		 */
		public function url_full($name, array $args = array(), $variation = 0)
		{
			return $this->request->protocol.'://'.$this->request->host.\System\Router::get_url($this->request->host, $name, $args, $variation);
		}


		/** Is the response readable?
		 * @return bool
		 */
		public function is_readable()
		{
			if ($this->request->user()->is_root() || empty($this->groups)) {
				return true;
			}

			$ids = $this->request->user()->get_group_ids();

			foreach ($ids as $id) {
				if (in_array($id, $this->groups)) {
					return true;
				}
			}

			return false;
		}


		public function init()
		{
			if (!$this->is_initialized()) {
				$this->cookie_store('lang', $this->locales->get_lang());

				\System\Init::run($this->init, array(
					"request"  => $this->request,
					"response" => $this,
				));
			}
		}


		public function is_initialized()
		{
			return $this->init_done;
		}
	}
}
