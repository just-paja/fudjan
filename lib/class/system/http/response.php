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
			self::FORBIDDEN             => "HTTP/1.1 403 Forbidden",
			self::PAGE_NOT_FOUND        => "HTTP/1.1 404 Page Not Found",
			self::INTERNAL_SERVER_ERROR => "HTTP/1.1 500 Internal Server Error",
		);

		protected static $attrs = array(
			"format"     => array('varchar'),
			"no_debug"   => array('bool'),
			"title"      => array('varchar'),
			"flow"       => array('object', "model" => '\System\Module\Flow'),
			"groups"     => array('list'),
			"locales"    => array('object', "model" => '\System\Locales'),
			"modules"    => array('list'),
			"init"       => array('list'),
			"policies"   => array('list'),
			"context"    => array('list'),
			"request"    => array('object', "model" => '\System\Http\Request'),
			"renderer"   => array('object', "model" => '\System\Template\Renderer'),
			"start_time" => array('float'),
			"sent"       => array('bool'),
			"pass"       => array('bool', 'default' => true),
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
		public static function from_request(\System\Http\Request $request, array $attrs = array())
		{
			def($attrs['format'], null);
			def($attrs['start_time'], microtime(true));

			if (is_null($attrs['format'])) {
				try {
					$attrs['format'] = \System\Settings::get("output", 'format_default');
				} catch (\System\Error $e) {
					$attrs['format'] = 'html';
				}
			}

			$response = new self($attrs);
			$response->data['request'] = $request;
			$response->data['flow']    = new \System\Module\Flow($response, $response->modules);
			$response->data['locales'] = \System\Locales::create($response, $request->lang)->make_syswide();
			return $response;
		}


		/** Execute modules
		 * @return $this
		 */
		public function exec()
		{
			return $this
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
				$this->pass = $policy($this->request(), $this);
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
					throw new \System\Error\Config('Policy set does not exist', $set);
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
					$data = $context($this->request(), $this);
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
			$this->renderer = $this->renderer()->render();
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
				try {
					$mime = \System\Output::get_mime($this->renderer()->format);
				} catch(\System\Error\Argument $e) {
					$mime = 'text/html; charset=utf-8';
				}

				if ($this->status == self::OK && empty($this->content)) {
					$this->status(self::NO_CONTENT);
				}

				header(self::get_status($this->status));

				foreach ($this->headers as $name => $content) {
					if (is_numeric($name)) {
						header($content);
					} else {
						header(ucfirst($name).": ".$content);
					}
				}

				header("Content-Type: ".$mime.";charset=utf-8");
				header("Content-Encoding: gz");
			}

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


		/** Get renderer object
		 * @return \System\Template\Renderer
		 */
		public function renderer()
		{
			if (!$this->renderer) {
				$this->data['renderer'] = \System\Template\Renderer::from_response($this);
			}

			return $this->renderer;
		}


		public function locales()
		{
			return $this->locales;
		}


		/** Clear response content
		 * @return $this
		 */
		public function flush()
		{
			$this->content['output'] = array();
			return $this;
		}


		/** Get flow object
		 * @return \System\Module\Flow
		 */
		public function flow()
		{
			return $this->flow;
		}


		/** Get request object
		 * @return \System\Http\Request
		 */
		public function request()
		{
			return $this->request;
		}


		/** Get full path including query string
		 * @return string
		 */
		public function path()
		{
			return $this->request()->path.($this->request()->query ? '?'.$this->request()->query:'');
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


		/** Run low level debug - Include a PHP file just after init and before module flow
		 * @return void
		 */
		public function exec_lld()
		{
			if (!$this->no_debug && \System\Settings::get('dev', 'debug', 'backend')) {
				if (file_exists(ROOT.'/lib/include/devel.php')) {
					$response = $this;
					$request  = $this->request();
					$renderer = $this->renderer();
					$locales  = $this->locales();
					$flow     = $this->flow();
					$ren      = &$renderer;

					include ROOT.'/lib/include/devel.php';
				}
			}

			return $this;
		}


		/** Shortcut for get_url
		 * @param String $name
		 * @param array  $args
		 * @param int    $variation
		 * @return String
		 */
		public function url($name, array $args = array(), $variation = 0)
		{
			return \System\Router::get_url($this->request()->host, $name, $args, $variation);
		}


		/** Is the response readable?
		 * @return bool
		 */
		public function is_readable()
		{
			if (!$this->request()->user()->is_root() && !empty($this->groups)) {
				foreach ($this->request()->user()->get_group_ids() as $id) {
					if (in_array($id, $this->groups)) {
						return true;
					}
				}

				return false;
			}

			return true;
		}


		public function init()
		{
			if (!$this->is_initialized()) {
				\System\Init::run($this->init, array(
					"request"  => $this->request(),
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
