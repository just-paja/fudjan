<?

namespace System
{
	class Page extends Model\Attr
	{
		const DIR_TEMPLATE_MODULES = "/lib/template/modules";

		private static $path = array();
		private static $input = array();
		private static $current;
		protected static $attrs = array(
			"string" => array('title', 'page', 'seoname', 'template', 'post', 'keywords', 'desc', 'robots', 'copyright', 'author'),
		);


		public static function init()
		{
			self::parse_path();
			self::$current = self::fetch_page();
		}


		public function __construct(array $dataray)
		{
			parent::__construct($dataray);

			if (strpos($this->get_path(), '/cron') === 0) {
				$this->template = array(null);
			}
		}


		public static function get_current()
		{
			return self::$current;
		}

		/** Parse page path
		 * @returns void
		 */
		private static function parse_path()
		{
			self::$path = array_filter(explode('/', substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?') ? strpos($_SERVER['REQUEST_URI'], '?'):strlen($_SERVER['REQUEST_URI']))));
			reset(self::$path) == Output::PREFIX_AJAX && Output::use_ajax(true);
		}


		/** Fetch page model from the tree
		 * @param mixed $path
		 * @param bool  $add_modules Add modules to the flow imediately?
		 */
		public static function fetch_page($search_path = null, $add_modules = true)
		{
			static $title;
			$iter = &Settings::get('pages');

			if (any($iter)) {
				$path = ($search_path) ? array_filter(explode('/', $search_path)):self::$path;
				$pd = self::browse_tree($iter, $path);

				if ($pd[1]['found']) {
					$pd['page_path'] = $path;
					$page = new self(array_merge($pd[0], $pd[1]));

					if ($add_modules) {
						$page->template && $page->fill_template();
						$page->add_modules();
					}

					return $page;
				}
			}
		}


		/** Browse page tree and fetch requested path
		 * @param &array $tree
		 * @param  array $path
		 * @returns array
		 */
		public static function browse_tree(&$tree, array $path)
		{
			$params = array(
				"found" => false,
			);
			$p = $path;
			$iter = &$tree;

			self::use_param("template", $iter, $params);
			self::use_param("admin_menu", $iter, $params);

			if (empty($path)) {
				$params['page_path'] = $path;
				$params['found'] = true;
				return array(&$iter['#'], $params);
			}

			do {
				$page = array_shift($p);
				$found = false;

				if (isset($iter[$page]) && is_array($iter[$page])) {
					$iter = &$iter[$page];
					$seoname = $page;
					$found = is_array($iter['#']);
					$template = any($iter["#"]["template"]);
				} elseif(isset($iter["*"]) && is_array($iter["*"])) {
					$iter = &$iter["*"];
					Input::add((array) 'page', $page);
					$found = is_array($iter['#']);
					$template = any($iter["#"]["template"]);
				} else {
					break;
				}

				if ($found) {
					if (any($iter["#"]['title'])) $params['title'] = def($title, '');
					$title = def($iter['#']['title'], '');

					self::use_param("template", $iter, $params);
					self::use_param("admin_menu", $iter, $params);
					self::use_param("seoname", $iter, $params);
					self::use_param("title", $iter, $params);
				}

				$params['found'] = !!$found;
			} while(!empty($p));

			return array(&$iter["#"], $params);;
		}


		/** Get page metadata
		 * @returns array
		 */
		public function get_meta()
		{
			$dataray = array();
			$meta = Settings::get('output', 'meta_tags');
			foreach((array) $meta as $name){
				if (!empty($this->data[$name])) $dataray[$name] = $this->data[$name];
			}
			return $dataray;
		}


		/** Add modules from current page into flow
		 * @returns System\Page
		 */
		public function add_modules()
		{
			if (!empty($this->opts['modules'])) {
				foreach((array) $this->opts['modules'] as $id=>$mod){
					$mod[1]['module_id'] = $id;
					Flow::add($mod[0], isset($mod[1]) ? $mod[1]:array(), isset($mod[2]) ? $mod[2]:array());
				}
			}

			return $this;
		}


		/** Use template modules
		 * @returns System\Page
		 */
		function fill_template()
		{
			if (count($this->template) > 0) {
				foreach ((array) $this->template as $t) {
					$modules = \System\Json::read(ROOT.self::DIR_TEMPLATE_MODULES."/".$t.".json", true);

					foreach ((array) $modules as $id=>$mod) {
						$mod[1]['module_id'] = $id;
						Flow::add($mod[0], isset($mod[1]) ? $mod[1]:array(), isset($mod[2]) ? $mod[2]:array());
					}
				}
			}

			return $this;
		}


		/** Update and save page modules
		 * @param array $modules
		 * @returns bool
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
		 * @returns string
		 */
		public static function get_path()
		{
			return '/'.implode('/', self::$path).(count(self::$path) > 0 ? '/':'');
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
		 * @returns bool
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
