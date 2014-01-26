<?

namespace System\Http
{
	class Request extends \System\Model\Attr
	{
		const IDENT_XHR = 'xmlhttprequest';


		protected static $attrs = array(
			"method"   => array('varchar'),
			"host"     => array('varchar'),
			"path"     => array('varchar'),
			"agent"    => array('varchar'),
			"query"    => array('varchar'),
			"lang"     => array('varchar', "is_null" => true),
			"referrer" => array('varchar'),
			"time"     => array('float'),
			"cli"      => array('bool'),
			"ajax"     => array('bool'),
			"args"     => array('list'),
			"get"      => array('list'),
			"post"     => array('list'),
			"secure"   => array('bool'),
			"user"     => array('object', "model" => '\System\User'),
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
					"host"     => $_SERVER['HTTP_HOST'],
					"path"     => $_SERVER['REQUEST_URI'],
					"referrer" => def($_SERVER['HTTP_REFERER']),
					"agent"    => $_SERVER['HTTP_USER_AGENT'],
					"query"    => $_SERVER['QUERY_STRING'],
					"time"     => def($_SERVER['REQUEST_TIME_FLOAT'], microtime(true)),
					"secure"   => any($_SERVER['HTTPS']),
					"method"   => strtolower(def($_SERVER['REQUEST_METHOD'])),
				);

				if ($data['query']) {
					$path = explode('?', $data['path']);
					$data['path'] = $path[0];
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
			$this->args = array();

			if (is_null($attrs)) {
				if ($path = \System\Router::get_path($this->host, $this->path, $this->data['args'])) {
					$attrs = isset($path[1]) ? $path[1]:array();
					$attrs['request'] = $this;

					return \System\Http\Response::from_request($this, $attrs);
				}
			} else {
				return \System\Http\Response::from_request($this, $attrs);
			}

			return false;
		}


		/** Get domain init data
		 * @return mixed
		 */
		private function get_init()
		{
			$domain = \System\Router::get_domain($this->host);

			try {
				$init = cfg('domains', $domain, 'init');
			} catch (\System\Error\Config $e) { $init = array(); }

			return $init;
		}


		/** Initialize all scripts for this request and domain
		 * @return void
		 */
		public function init()
		{
			if ($this->get('lang')) {
				\System\Locales::set_lang($this->get('lang'));
			}

			\System\Init::run($this->get_init(), array("request" => $this));
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
			$data = array();

			foreach ($this->data[$method] as $k=>&$v) {
				if (strpos($k, $prefix) === 0) $data[substr($k, strlen($prefix))] = &$v;
			}

			return $data;
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


		/** Get current active user
		 * @return System\User
		 */
		public function user()
		{
			if ($this->user instanceof \System\User) {
				return $this->user;
			} elseif (any($_SESSION[\System\User::COOKIE_USER])) {
				$this->user = find("\System\User", $_SESSION[\System\User::COOKIE_USER]);
			}

			if (!($this->user instanceof \System\User)) {
				$this->user = \System\User::guest();
			}

			$this->user->get_rights();
			return $this->user;
		}


		/** Is anyone logged in?
		 * @return bool
		 */
		public function logged_in()
		{
			return !!$this->user()->id;
		}
	}
}
