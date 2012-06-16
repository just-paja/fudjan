<?

namespace System
{
	class Page extends Model\Attr
	{

		const DIR_TEMPLATE_MODULES = "/lib/template/modules";
		static protected $id_col = "id_page";
		static protected $required_attrs = array();
		static protected $attrs = array(
			"string" => array('title', 'page', 'seoname', 'template', 'post', 'keywords', 'desc', 'robots', 'copyright', 'author'),
		);

		static $path = array();
		static $input = array();


		public static function init()
		{
			self::parse_path();
		}


		public static function parse_path()
		{
			self::$path = array_filter(explode('/', substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?') ? strpos($_SERVER['REQUEST_URI'], '?'):strlen($_SERVER['REQUEST_URI']))));

			if(preg_match("/\.([a-zA-Z])+$/", end(self::$path))){
				Output::set_format(last_part('.', end(self::$path)));
				self::$path = remove_last_part('.', end(self::$path));
			}

			reset(self::$path) == Output::PREFIX_AJAX && Output::use_ajax(true);
			return self::$path;
		}


		static function fetch_page($search_path = null, $add_modules = true)
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
						Status::log("Page path", array(self::get_path()), true);
					}

					return $page;
				}
			} else {
				Settings::get('dev', 'debug') ?
					die(_('Chyba konfigurace - nenalezeny žádné stránky.')):
					Flow::redirect_now(array("url" => '/share/html/no-pages.html'));
			}
		}


		static function browse_tree(&$tree, array $path)
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


		function get_meta()
		{
			$dataray = array();
			$meta = Settings::get('output', 'meta_tags');
			foreach((array) $meta as $name){
				if (!empty($this->data[$name])) $dataray[$name] = $this->data[$name];
			}
			return $dataray;
		}


		function add_modules()
		{
			if (!empty($this->opts['modules'])) {
				foreach((array) $this->opts['modules'] as $id=>$mod){
					$mod[1]['module_id'] = $id;
					Flow::add($mod[0], isset($mod[1]) ? $mod[1]:array(), isset($mod[2]) ? $mod[2]:array());
				}
			}
		}


		function fill_template()
		{
			if (count($this->template) > 0) {
				foreach ((array) $this->template as $t) {
					if (file_exists($p = ROOT.self::DIR_TEMPLATE_MODULES."/".$t.".json")) {
						$modules = json_decode(file_get_contents($p), true);
						foreach ((array) $modules as $id=>$mod) {
							$mod[1]['module_id'] = $id;
							Flow::add($mod[0], isset($mod[1]) ? $mod[1]:array(), isset($mod[2]) ? $mod[2]:array());
						}
					}
				}
			}
		}


		function update_modules($modules)
		{
			$tree = &Settings::get('pages');
			$page = &self::browse_tree($tree, $this->page_path);
			$iter = &$page[0];

			$iter['modules'] = $modules;
			Settings::save('pages');
			return true;
		}


		public static function get_path()
		{
			return '/'.implode('/', self::$path);
		}


		// inherit page tree parameters
		private static function use_param($name, array &$node, array &$default)
		{
			if (isset($node["#"][$name])) {
				$default[$name] = $node["#"][$name];
			} else {
				$node["#"][$name] = isset($default[$name]) ? $default[$name]:null;
			}
		}


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


		function __construct(array $dataray)
		{
			parent::__construct($dataray);

			if (strpos($this->get_path(), '/cron') === 0) {
				$this->template = array(null);
			}
		}
	}
}
