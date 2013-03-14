<?

/** Function aliases that make life easier
 * @package core
 */
namespace
{
	// Tag class total alias, put it into root namespace
	class Tag extends System\Template\Tag {}

	// Silent tag class
	class Stag extends Tag {
		public static function __callStatic($name, $args)
		{
			$attrs = &$args[0];
			$attrs['output'] = false;
			return parent::tag($name, $attrs);
		}
	}

	// BasicModel & ExtModel aliases
	/** @alias System\Model\Database::get_all */
	function get_all($model, array $conds = array(), array $opts = array(), array $joins = array()) {
		return System\Model\Database::get_all($model, $conds, $opts, $joins);
	}

	/** @alias System\Model\Database::get_first */
	function get_first($model, array $conds = array(), array $opts = array(), array $joins = array()) {
		return System\Model\Database::get_first($model, $conds, $opts, $joins);
	}

	/** @alias System\Model\Database::count */
	function count_all($model, array $conds = array(), array $opts = array(), array $joins = array()) {
		return System\Model\Database::count_all($model, $conds, $opts, $joins);
	}

	/** @alias System\Model\Database::find */
	function find($model, $ids = array(), $force_array = false) {
		return System\Model\Database::find($model, $ids, $force_array);
	}

	/** @alias System\Model\Database::create */
	function create($model, array $attrs) {
		return System\Model\Database::create($model, $attrs);
	}


	// Messages
	function message($status, $title, $message=null, $autohide=false, $links = array()) {
		$msg = new System\Message($status, $title, $message, $autohide, $links, false);
		return $msg->get_retval();
	}

	function t_message($status, $title, $message=null, $autohide=false, $links = array()) {
		$msg = new System\Message($status, $title, $message, $autohide, $links, false);
		System\Template::partial("system/message", array("message" => $msg));
		return $msg->get_retval();
	}


	// Redirects
	/** @alias System\Http::redirect */
	function redirect_now($url, $code=\System\Http::FOUND) { return System\Http::redirect($url, $code); }

	/** @alias System\Flow::redirect */
	function redirect($url, $code=\System\Http::FOUND, $when = \System\Flow::REDIRECT_AFTER_MODULES) { return System\Flow::redirect($url, $code, $when); }


	// Template
	/** @alias System\Template::link_for */
	function link_for($label, $url=null, $object=array()) {
		return System\Template::link_for($label, $url, $object);
	}

	/** @alias System\Template::icon_for */
	function icon_for($icon, $size=32, $url, $label = NULL, $object = array()) {
		return System\Template::icon_for($icon, $size, $url, $label, $object);
	}

	/** @alias System\Template::label_for */
	function label_for($icon, $size=32, $label, $url, $object = array()) {
		return System\Template::label_for($icon, $size, $url, $label, $object);
	}

	/** @alias System\Template::label_right_for */
	function label_right_for($icon, $size=32, $label, $url, $object = array()) {
		return System\Template::label_right_for($icon, $size, $url, $label, $object);
	}

	/** @alias System\Template::icon */
	function icon($icon, $size=32, array $attrs = array()) {
		return System\Template::icon($icon, $size, $attrs);
	}

	/** @alias System\Template::format_date */
	function format_date($datetime = null, $format = 'std') {
		return System\Template::format_date($datetime, $format);
	}

	/** @alias System\Template::heading */
	function heading($label, $save_level = true, $level = NULL) {
		return System\Template::heading($label, $save_level, $level);
	}

	/** @alias System\Template::section_heading */
	function section_heading($label, $level = NULL) {
		return System\Template::section_heading($label, $level);
	}

	/** @alias System\Output::content_for */
	function content_for($place, $content, $overwrite = false) {
		return System\Output::content_for($place, $content, $overwrite);
	}

	/** @alias System\Output::content_from */
	function content_from($place) {
		return System\Output::content_from($place);
	}

	/** @alias System\Output::slot */
	function slot($name = System\Template::DEFAULT_SLOT) {
		return System\Output::slot($name);
	}

	/** @alias System\Output::yield */
	function yield() {
		return System\Output::yield();
	}

	/** @alias System\Output::title */
	function title() {
		return System\Output::get_title(true);
	}

	/** @alias System\Page::get_path */
	function path() {
		return System\Page::get_path();
	}

	/** @alias System\Template::get_css_color */
	function get_css_color($color) {
		return System\Template::get_css_color($color);
	}

	/** @alias System\Template::get_color_container */
	function get_color_container($color) {
		return System\Template::get_color_container($color);
	}

	/** @alias System\User::get_active */
	function user() {
		return System\User::get_active();
	}

	/** @alias System\User::logged_in */
	function logged_in() { return System\User::logged_in(); }

	/** @alias System\Locales::strlen_binary */
	function strlen_binary($str) {
		return System\Locales::strlen_binary($str);
	}

	/** @alias System\Locales::translate */
	function l($str, $lang = null) {
		return System\Locales::translate($str, $lang);
	}

	/** @alias System\Locales::translate_and_replace */
	function t($str) {
		return System\Locales::translate_and_replace($str, func_get_args());
	}

	/** @alias System\Settings::get */
	function cfg() {
		return System\Settings::get(func_get_args());
	}

	/** @alias System\Settings::set */
	function cfgs(array $path, $value) {
		return System\Settings::set($path, $value);
	}
}
