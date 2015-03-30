<?

namespace System
{
	class Resource extends \System\Model\Attr
	{
		const TYPE_STYLES  = 'style';
		const TYPE_PIXMAPS = 'pixmap';
		const TYPE_ICONS   = 'icon';
		const TYPE_THUMB   = 'thumb';
		const TYPE_FONT    = 'font';

		const DIR_CACHE    = '/var/cache/resources';
		const DIR_MEDIA    = '/var/files';
		const DIR_STATIC   = '/share';

		const KEY_SUM              = 'sum';
		const KEY_TYPE             = 'type';
		const KEY_FOUND            = 'found';
		const KEY_MISSING          = 'missing';
		const KEY_DIR_FILES        = 'modules';
		const KEY_DIR_CONTENT      = 'content';
		const KEY_STRING_NOT_FOUND = 'not_found_string';
		const KEY_POSTFIXES        = 'postfixes';
		const KEY_CALLBACK_RESOLVE = 'resolve';
		const KEY_CALLBACK_PARSE   = 'parse';

		const MAX_AGE = 86400;

		const MIME_TYPE = 'text/plain; charset=utf-8';
		const NOT_FOUND = null;
		const PARSER    = null;
		const RESOLVER  = null;
		const POSTFIX_OUTPUT = null;

		private static $serial = null;

		protected $content = '';


		static private $map = array(
			'font'   => '\\System\\Resource\\Font',
			'icon'   => '\\System\\Resource\\Icon',
			'pixmap' => '\\System\\Resource\\Pixmap',
			'script' => '\\System\\Resource\\Script',
			'style'  => '\\System\\Resource\\Style',
			'thumb'  => '\\System\\Resource\\Thumb',
			'locale' => '\\System\\Resource\\Locale',
		);


		static protected $attrs = array(
			'debug'     => array("type" => 'boolean'),
			'exists'    => array("type" => 'boolean', 'default' => true),
			'mime'      => array("type" => 'string'),
			'path'      => array("type" => 'string'),
			'response'  => array("type" => 'object', "model" => '\System\Http\Response'),
			'request'   => array("type" => 'object', "model" => '\System\Http\Request'),
			'serial'    => array("type" => 'int'),
			'src'       => array("type" => 'string'),
			'type'      => array("type" => 'string'),
			'use_cache' => array("type" => 'boolean'),
		);


		/**
		 * Serve request
		 * @return void
		 */
		static public function sort_out(array $opts)
		{
			try {
				$opts['debug'] = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error $e) {
				$opts['debug'] = true;
			}

			try {
				$opts['cache'] = \System\Settings::get('cache', 'resources');
			} catch (\System\Error $e) {
				$opts['cache'] = false;
			}

			$map = self::get_map();

			if (array_key_exists($opts['type'], $map)) {
				$cname = $map[$opts['type']];
				return new $cname($opts);
			}

			if ($opts['debug']) {
				throw new \System\Error\Argument('Invalid resource type', $opts['type'], $opts);
			} else {
				throw new \System\Error\NotFound();
			}
		}


		public static function get_map()
		{
			try {
				$map_extra = \System\Settings::get('resources', 'map');
			} catch (\System\Settings $e) {
				$map_extra = array();
			}

			return array_merge(self::$map, $map_extra);
		}


		public static function get_serial()
		{
			if (is_null(self::$serial)) {
				try {
					$debug = \System\Settings::get('dev', 'debug', 'backend') || \System\Settings::get('dev', 'disable', 'serial');
				} catch(\System\Error $e) {
					$debug = true;
				}

				if ($debug) {
					self::$serial = rand(0, PHP_INT_MAX);
				} else {
					self::$serial = \System\Settings::get('cache', 'resource', 'serial');
				}
			}

			return self::$serial;
		}


		public static function get_url($src, $type, $name)
		{
			try {
				$domain = \System\Settings::get('resources', 'domain');
			} catch (\System\Error\Config $e) {
				$domain = null;
			}

			$map  = self::get_map();
			$post = null;

			if (isset($map[$type])) {
				$cname = $map[$type];

				if ($cname::POSTFIX_OUTPUT) {
					$post = $cname::POSTFIX_OUTPUT;
				}
			}

			if (!$post && strpos($name, '.')) {
				$name = explode('.', $name);
				$post = '.'.array_pop($name);
				$name = implode('.', $name);
			}

			$name = $name.'.'.self::get_serial().($post ? $post:'');
			$path = \System\Router::get_first_url('system_resource', array($src, $type, $name));

			if ($domain) {
				$path = '//'.$domain.$path;
			}

			return $path;
		}


		public function construct()
		{
			$this->strip_serial();

			try {
				$this->use_cache = \System\Settings::get('cache', 'resources');
			} catch(\System\Error\Config $e) {
				$this->use_cache = false;
			}
		}


		public function strip_serial()
		{
			$matches = null;
			$match   = preg_match('/^(.+)\.([0-9]+)(\.([a-zA-Z]+))?/', $this->path, $matches);

			if ($match) {
				$this->name = $matches[1];

				if (array_key_exists(3, $matches)) {
					$this->name_full = $matches[1].$matches[3];
					$this->postfix = $matches[4];
				} else {
					$this->name_full = $this->name;
				}

				$this->serial = $matches[2];
			}
		}


		public function serve()
		{
			$this->resolve();

			if ($this->exists) {
				if ($this->debug || $this->serial == self::get_serial()) {
					$this->read();
					$this->cache_content();
					$this->set_headers();
					$this->set_response();

					$this->response->skip_render = true;
				} else {
					$this->redirect_to_current();
				}
			} else {
				throw new \System\Error\NotFound();
			}
		}


		public function cache_content()
		{
		}


		public function redirect_to_current()
		{
			$this->response->redirect($this->get_url($this->src, $this->type, $this::POSTFIX_OUTPUT ? $this->name:$this->name_full));
		}


		public function set_headers()
		{
			$headers = array();
			$res = $this->response;

			if ($this->mime) {
				$res->header('Content-Type', $this->mime);
			} else if ($this::MIME_TYPE) {
				$res->header('Content-Type', $this::MIME_TYPE);
			}

			$res->header('Content-Length', strlen($this->content));
			$res->header('Access-Control-Allow-Origin', '*');

			if (!$this->debug) {
				$max_age = \System\Settings::get('cache', 'resource', 'max-age');

				$res->header('Pragma', 'public, max-age='.$max_age);
				$res->header('Cache-Control', 'public');
				$res->header('Expires', date(\DateTime::RFC1123, time() + $max_age + rand(0,60)));
				$res->header('Age', '0');
			}
		}


		public function set_response()
		{
			$this->response->set_content($this->content);
		}
	}
}
