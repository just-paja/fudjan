<?

namespace System\Http
{
	class Request extends \System\Model\Attr
	{
		const IDENT_XHR = 'xmlhttprequest';


		protected static $attrs = array(
			"method"   => array("type" => 'varchar'),
			"host"     => array("type" => 'varchar'),
			"path"     => array("type" => 'varchar'),
			"agent"    => array("type" => 'varchar'),
			"query"    => array("type" => 'varchar'),
			"lang"     => array("type" => 'varchar', "is_null" => true),
			"referrer" => array("type" => 'varchar'),
			"time"     => array("type" => 'float'),
			"cli"      => array("type" => 'bool'),
			"ajax"     => array("type" => 'bool'),
			"args"     => array("type" => 'list'),
			"params"   => array("type" => 'array'),
			"get"      => array("type" => 'list'),
			"post"     => array("type" => 'list'),
			"protocol" => array("type" => 'varchar'),
			"secure"   => array("type" => 'bool'),
			"user"     => array("type" => 'object', "model" => '\System\User'),
			"policies" => array("type" => 'list'),
			"context"  => array("type" => 'list'),
			"init"     => array("type" => 'list'),
			'rules'    => array("type" => 'list'),
			'session'  => array("type" => 'list'),
		);


		/** Create request object from current hit
		 * @return self
		 */
		public static function from_hit()
		{
			if (\System\Status::on_cli()) {
				$data = array(
					"time"   => def($_SERVER['REQUEST_TIME_FLOAT'], microtime(true)),
					"cli"    => true,
					"secure" => false,
				);
			} else {
				$data = array(
					"cli"      => false,
					"ajax"     => strtolower(def($_SERVER['HTTP_X_REQUESTED_WITH'])) == self::IDENT_XHR,
					"host"     => def($_SERVER['HTTP_HOST']),
					"path"     => def($_SERVER['REQUEST_URI']),
					"protocol" => def($_SERVER['REQUEST_SCHEME']),
					"referrer" => def($_SERVER['HTTP_REFERER']),
					"agent"    => def($_SERVER['HTTP_USER_AGENT']),
					"query"    => def($_SERVER['QUERY_STRING']),
					"time"     => def($_SERVER['REQUEST_TIME_FLOAT'], microtime(true)),
					"secure"   => any($_SERVER['HTTPS']),
					"method"   => strtolower(def($_SERVER['REQUEST_METHOD'])),
					"cookies"  => &$_COOKIE,
					"session"  => &$_SESSION,
				);

				if ($data['query']) {
					$path = explode('?', $data['path']);
					$data['path'] = $path[0];
				}

				if (isset($_GET['path'])) {
					$data['path'] = $_GET['path'];
				}
			}

			$obj = new self($data);
			return $obj->prepare_input();
		}


		/** Get page by request path
		 * @return \System\Page|bool
		 */
		public function create_response(array $attrs = null)
		{
			$args = array();
			$params = array();
			$response = false;

			if (is_null($attrs)) {
				$domain = \System\Router::get_domain($this->host);

				if ($domain) {
					$path = \System\Router::get_path($domain, $this->path, $args, $params);

					if ($path) {
						$path['request'] = $this;

						$this->args   = $args;
						$this->params = $params;

						$response = \System\Http\Response::from_request($this, $path);
					}
				}
			} else {
				$response = \System\Http\Response::from_request($this, $attrs);
			}

			return $response;
		}


		/** Get domain init data
		 * @return mixed
		 */
		public function load_config()
		{
			$cfg = cfg('domains', \System\Router::get_domain($this->host));
			$this->rules    = def($cfg['rules'], null);
			$this->layout   = (array) def($cfg['layout'], array());
			$this->policies = (array) def($cfg['policies'], array());
			$this->context  = (array) def($cfg['context'], array());
			$this->init     = (array) def($cfg['init'], array());

			return $this;
		}


		/** Initialize all scripts for this request and domain
		 * @return void
		 */
		public function init()
		{
			if ($this->get('lang')) {
				$this->lang = $this->get('lang');
			} else if ($this->cookie('lang')) {
				$this->lang = $this->cookie('lang');
			}

			try {
				$login = \System\Settings::get('policies', 'auto_login');
			} catch (\System\Error\Config $e) {
				$login = true;
			}

			if ($login) {
				$this->relogin();
			}

			\System\Init::run($this->init, array("request" => $this));
			return $this;
		}


		/** Strip slashes if magic quotes are on
		 * @param array &$data
		 * @return void
		 */
		private function fix_input(array &$data)
		{
			if (get_magic_quotes_gpc()) {
				foreach ($data as &$row) {
					if (is_array($row)) {
						self::fix_input($row);
					} else {
						$row = stripcslashes($row);
					}
				}
			}
		}


		/** Public init
		 * @return void
		 */
		private function prepare_input()
		{
			if (!isset($_GET)) $_GET = array();
			if (!isset($_POST)) $_POST = array();
			if (!isset($_FILES)) $_FILES = array();

			foreach ($_FILES as $var=>$cont) {
				if (isset($cont['name']) && is_array($cont['name'])) {
					foreach ($cont as $attr=>$value) {
						foreach ($value as $file=>$file_attr) {
							if ($a = is_array($file_attr)) {
								$_FILES[$var][$file][$attr] = $file_attr['file'];
							} else {
								$_FILES[$var][$attr] = $file_attr;
							}
						}

						if ($a) unset($_FILES[$var][$attr]);
					}
				} else {
					break;
				}
			}

			$this->get  = $_GET;
			$this->post = array_merge($_POST, $_FILES);

			foreach (array('get', 'post') as $key) {
				$this->fix_input($this->data[$key]);
			}

			// Prevent using unescaped vars
			unset($_GET, $_POST, $_FILES);
			return $this;
		}



		/** Get input data
		 * @usage get(path, path, path, ..)
		 * @param array|string $path Path of input
		 * @return mixed
		 */
		public function input($path = null)
		{
			if (func_num_args()) {
				$path = func_get_args();

				if (is_array(func_get_arg(0))) {
					$args = array_shift($path);
					$path = array_merge($args, $path);
				}

				$iter = &$this->data;
				foreach($path as $arg) {
					if (isset($iter) && is_array($iter)) $iter = &$iter[$arg];
					else $iter = array();
				}

				return self::secure_input($iter);
			} else throw new \System\Error\Argument("You must define input path you want to get.");
		}


		/** Get data with key, that starts with specific prefix
		 * @param string $method get or post
		 * @param string $prefix
		 * @return array
		 */
		public function input_by_prefix($prefix, $method = 'post')
		{
			if ($prefix) {
				$data = array();

				foreach ($this->data[$method] as $k=>&$v) {
					if (strpos($k, $prefix) === 0) $data[substr($k, strlen($prefix))] = &$v;
				}

				return $data;
			}

			return $this->data[$method];
		}


		/** Escape input
		 * @param string $str
		 * @return string
		 */
		public static function secure_input($str)
		{
			$bad = array("'", "`", "\"");
			$good = array("&#39;", "&#96;", "&quot;");

			return $str = str_replace($bad, $good, $str);
		}


		/** Get data from GET request data
		 * @return mixed
		 */
		public function get()
		{
			$args = func_get_args();
			array_unshift($args, 'get');
			return $this->input($args);
		}


		/** Get data from POST request data
		 * @return mixed
		 */
		public function post()
		{
			$args = func_get_args();
			array_unshift($args, 'post');
			return $this->input($args);
		}


		/** Get data from POST request data
		 * @return mixed
		 */
		public function cookie($name)
		{
			return isset($this->cookies[$name]) ? $this->cookies[$name]:null;
		}


		/** Get data from POST request data
		 * @return mixed
		 */
		public function sess($name)
		{
			return isset($this->session[$name]) ? $this->session[$name]:null;
		}


		/** Get current active user
		 * @return System\User
		 */
		public function relogin()
		{
			$cookie = \System\User::COOKIE_USER;

			if ($this->user) {
				return $this->user;
			}

			if ($this->sess($cookie)) {
				$this->user = \System\User::find($this->sess($cookie));
			}

			if (!($this->user)) {
				$this->user = \System\User::guest();
			}

			$this->user->get_rights();
		}


		/** Is anyone logged in?
		 * @return bool
		 */
		public function logged_in()
		{
			return $this->user && $this->user->id;
		}
	}
}
