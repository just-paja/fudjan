<?

namespace System
{
	class Page extends Model\Attr
	{
		private static $path = array();
		private static $input = array();
		private static $current;
		protected static $attrs = array(
			"title"     => array('varchar'),
			"page"      => array('varchar'),
			"path"      => array('varchar'),
			"seoname"   => array('varchar'),
			"modules"   => array('list'),
			"template"  => array('list'),
			"variable"  => array('list'),
			"post"      => array('varchar'),
			"keywords"  => array('varchar'),
			"desc"      => array('text'),
			"robots"    => array('varchar'),
			"copyright" => array('varchar'),
			"author"    => array('varchar'),
		);


		/** Init page class
		 */
		public static function init()
		{
		}


		public function __construct(array $dataray)
		{
			parent::__construct($dataray);

			if (strpos($this->path, '/cron') === 0) {
				$this->template = array(null);
			}
		}


		/** Get (and find if none) current page
		 * @return System\Page|false
		 */
		public static function get_current()
		{
			if (empty(self::$current)) {
				self::$current = self::fetch_page();
			}

			return self::$current;
		}


		/** Set current page
		 * @param System\Page $page
		 */
		public static function set_current(self $page)
		{
			return self::$current = $page;
		}


		/** Parse page path
		 * @return void
		 */
		private static function parse_path()
		{
			self::$path = array();

			foreach (array_filter(explode('/', substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?') ?
				strpos($_SERVER['REQUEST_URI'], '?'):strlen($_SERVER['REQUEST_URI']))
			)) as $p) {
				self::$path[] = $p;
			}
		}


		/** Fetch page model from the tree
		 * @param mixed $path
		 * @param bool  $add_modules Add modules to the flow imediately?
		 * @return System\Page|false
		 */
		public static function fetch_page($search_path = null, $add_modules = true)
		{
			$iter = &\System\Settings::get('pages');

			if (any($iter)) {
				$path = is_null($search_path) ? self::$path:array_filter(explode('/', $search_path));
				array_unshift($path, $_SERVER['HTTP_HOST']);

				$pd = self::browse_tree($iter, $path);

				if ($pd) {
					array_shift($path);
					$pd['path'] = '/'.implode('/', $path).(count($path) > 0 ? '/':'');
					$page = new self($pd);

					if ($add_modules) {
						$page->add_modules();
					}

					return $page;
				}
			}

			return false;
		}


		/** Browse page tree and fetch requested path
		 * @param &array $tree
		 * @param  array $path
		 * @return &array|false
		 */
		public static function browse_tree(&$tree, array $path, $return_anchor = true)
		{
			$params = array();
			$found  = true;
			$variable = array();
			$p = $path;
			$iter = &$tree;

			$meta = cfg('output', 'meta_tags');
			self::use_param("template", $iter, $params);
			self::use_param("seoname", $iter, $params);
			self::use_param("title", $iter, $params);

			foreach ($meta as $tag) {
				self::use_param($tag, $iter, $params);
			}

			while (!empty($p)) {
				$page = array_shift($p);
				$found = false;

				if (isset($iter[$key = $page]) || isset($iter[$key = "*"])) {
					if (is_array($iter[$key]) && is_array($iter[$key]['#'])) {
						$iter    = &$iter[$key];
						$seoname = $key;
						$found   = true;

						if ($key === '*') {
							$variable[] = $page;
						}
					} else throw new \System\Error\Format('Malformed path "%s". Page must be array, have anchor which also has to be an array.');
				} else {

					foreach ($iter as $key=>&$item) {
						$matches = array();
						$key_str = str_replace(array('\\', '/'), array('', '\\/'), $key);
						$slashes = substr_count($key_str, '/');

						for ($i = 0; $i<$slashes; $i ++) {
							$page .= '/'.array_shift($p);
						}

						$match = @preg_match('/'.$key_str.'/', $page, $matches);

						if ($match !== false) {
							if ($match) {
								if (is_array($item) && is_array($item['#'])) {
									$iter    = &$item;
									$seoname = $matches[0];
									$found = true;

									$variable[] = $matches[0];
									break;
								} else throw new \System\Error\Format('Malformed path "%s". Page must be array, have anchor which also has to be an array.');
							}
						} else throw new \System\Error\Format(sprintf('Malformed regular expression "%s" for path "%s"', $key_str, join('/', $path)));
					}

					if (!$found) {
						$found = false;
						break;
					}
				}

				if ($found) {
					if (any($iter["#"]['title'])) $params['title'] = def($title, '');
					$title = def($iter['#']['title'], '');

					self::use_param("template", $iter, $params);
					self::use_param("seoname", $iter, $params);
					self::use_param("title", $iter, $params);

					foreach ($meta as $tag) {
						self::use_param($tag, $iter, $params);
					}
				}
			}

			$iter['#'] = array_merge($params, $iter['#']);
			$iter['#']['variable'] = $variable;

			if ($found) {
				if ($return_anchor) {
					return $iter["#"];
				} else {
					return $iter;
				}
			} else return $found;
		}


		/** Get page metadata
		 * @return array
		 */
		public function get_meta()
		{
			$dataray = array();
			$meta = cfg('output', 'meta_tags');

			foreach ((array) $meta as $name) {
				if (!empty($this->data[$name])) $dataray[$name] = array("name" => $name, "content" =>$this->data[$name]);
			}

			return $dataray;
		}


		/** Add modules from current page into flow
		 * @return System\Page
		 */
		public function add_modules()
		{
			foreach ($this->modules as $id=>$mod){
				$mod[1]['module_id'] = $id;
				Flow::add($mod[0], isset($mod[1]) ? $mod[1]:array(), isset($mod[2]) ? $mod[2]:array());
			}

			return $this;
		}


		/** Update and save page modules
		 * @param array $modules
		 * @return bool
		 */
		function update_modules(array $modules)
		{
			$tree = &Settings::get('pages');
			$page = &self::browse_tree($tree, $this->page_path);
			$iter = &$page[0];

			$iter['modules'] = $modules;
			return Settings::save('pages');
		}


		/** Return page path
		 * @return string
		 */
		public static function get_path()
		{
			if (self::$current) {
				return self::get_current()->path;
			} else {
				return '/'.implode('/', self::$path).(count(self::$path) > 0 ? '/':'');
			}
		}


		/** Get path as array
		 * @return array
		 */
		public static function get_path_list()
		{
			return self::$path;
		}


		/** Get variables from parsed page path
		 * @return array
		 */
		public static function get_path_variables()
		{
			if (self::get_current()) {
				return self::get_current()->variable;
			} else return array();
		}


		/** Inherit page tree ancestors' parameters
		 * @param  string $name Parameter name
		 * @param &array  $node
		 * @param &array  $default
		 */
		private static function use_param($name, array &$node, array &$default)
		{
			if (isset($node["#"][$name])) {
				$default[$name] = $node["#"][$name];
			} else {
				$node["#"][$name] = isset($default[$name]) ? $default[$name]:null;
			}
		}


		/** Is the page readable?
		 * @return bool
		 */
		public function is_readable()
		{
			if (!user()->is_root() && !empty($this->opts['groups'])) {
				foreach (user()->get_group_ids() as $id) {
					if (in_array($id, $this->opts['groups'])) return true;
				}
				return false;
			}

			return true;
		}
	}
}
