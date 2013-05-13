<?

namespace System\Http
{
	class Request extends \System\Model\Attr
	{
		protected static $attrs = array(
			"host"  => array('varchar'),
			"path"  => array('varchar'),
			"agent" => array('varchar'),
			"query" => array('varchar'),
			"time"  => array('float'),
			"cli"   => array('bool'),
			"args"  => array('list'),
			"get"   => array('list'),
			"post"  => array('list'),
		);


		public static function from_hit()
		{
			if (\System\Status::on_cli()) {
				$data = array(
					"time"  => $_SERVER['REQUEST_TIME_FLOAT'],
					"cli"   => true,
				);
			} else {
				$data = array(
					"host"  => $_SERVER['HTTP_HOST'],
					"path"  => $_SERVER['REQUEST_URI'],
					"agent" => $_SERVER['HTTP_USER_AGENT'],
					"query" => $_SERVER['QUERY_STRING'],
					"time"  => $_SERVER['REQUEST_TIME_FLOAT'],
				);

				if ($data['query']) {
					$path = explode('?', $data['path']);
					$data['path'] = $path[0];
				}
			}

			$obj = new self($data);
			return $obj->prepare_input();
		}


		public function get_page()
		{
			$this->args = array();

			if ($path = \System\Router::get_path($this->host, $this->path, $this->data['args'])) {
				return new \System\Page(isset($path[1]) ? $path[1]:array());
			}

			return false;
		}


		public function get_init()
		{
			$domain = \System\Router::get_domain($this->host);
			return cfg('domains', $domain, 'init');
		}


		public function init()
		{
			foreach ($this->get_init() as $init_step) {
				if (file_exists($f = ROOT.'/etc/init.d/'.$init_step.'.php')) {
					require_once($f);
				} else throw new \System\Error\File(sprintf("Init file '%s' was not found inside init folder '%s'.", $init_step, \System\Init::DIR_INIT));
			}
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
	}
}
