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
		);


		public static function from_hit()
		{
			return new self(array(
				"host"  => $_SERVER['HTTP_HOST'],
				"path"  => $_SERVER['REQUEST_URI'],
				"agent" => $_SERVER['HTTP_USER_AGENT'],
				"query" => $_SERVER['QUERY_STRING'],
				"time"  => $_SERVER['REQUEST_TIME_FLOAT'],
			));
		}


		public function get_page()
		{
			if ($path = \System\Router::get_path($this->host, $this->path)) {
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
	}
}
