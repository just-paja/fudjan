<?

/** Function aliases that make life easier
 * @package core
 */
namespace
{
	// Tag class total alias, put it into root namespace
	class Tag extends System\Template\Tag {}

	/** Silent tag class
	 * @package core
	 * @subpackage aliases
	 */
	class Stag extends Tag {
		public static function __callStatic($name, $args)
		{
			$attrs = &$args[0];
			$attrs['output'] = false;
			return parent::tag($name, $attrs);
		}
	}


	function close($tagname)
	{
		return Tag::close($tagname);
	}


	function introduce()
	{
		return \System\Output::introduce();
	}


	function doctype()
	{
		return Tag::doctype();
	}


	function footer($class = null, $content = null)
	{
		return Tag::tag('footer', array(
			"class"   => $class,
			"content" => $content,
			"output"  => false,
		));
	}


	function htmlheader($class = null, $content = null)
	{
		return Tag::tag('header', array(
			"class"   => $class,
			"content" => $content,
			"output"  => false,
		));
	}


	function body($class = null, $content = null)
	{
		return Tag::tag('div', array(
			"class"   => $class,
			"content" => $content,
			"output"  => false,
		));
	}


	/** Div tag alias
	 * @param string|array $class   Classname passed to the div
	 * @param string|array $content Content rendered inside div
	 * @param string       $id      ID attribute of the div
	 * @return string
	 */
	function div($class = null, $content = null, $id = null)
	{
		return Tag::tag('div', array(
			"class"   => $class,
			"content" => $content,
			"id"      => $id,
			"output"  => false,
		));
	}


	/** Span tag alias
	 * @param string|array $class   Classname passed to the div
	 * @param string|array $content Content rendered inside div
	 * @param string       $id      ID attribute of the div
	 * @return string
	 */
	function span($class, $content = null, $id = null)
	{
		return Tag::tag('span', array(
			"class"   => $class,
			"content" => $content,
			"id"      => $id,
			"output"  => false,
		));
	}


	/** UL tag alias
	 * @param string|array $class   Classname passed to the div
	 * @param string|array $content Content rendered inside div
	 * @param string       $id      ID attribute of the div
	 * @return string
	 */
	function ul($class, $content = null, $id = null)
	{
		return Tag::tag('ul', array(
			"class"   => $class,
			"content" => $content,
			"id"      => $id,
			"output"  => false,
		));
	}


	/** LI tag alias
	 * @param string|array $content Content rendered inside
	 * @param string|array $class   Classname passed to the tag
	 * @param string       $id      ID attribute of the tag
	 */
	function li($content = null, $class = null, $id = null)
	{
		return Tag::tag('li', array(
			"class"   => $class,
			"content" => $content,
			"id"      => $id,
			"output"  => false,
		));
	}


	/** IMG tag alias
	 * @param string       $src   Path to image source
	 * @param string       $alt   Alternative text
	 * @param string|array $class Classname passed to the image
	 * @param string       $id    ID attribute for image
	 */
	function img($src, $alt = '', $class = null, $id = null)
	{
		return Tag::tag('img', array(
			"class"  => $class,
			"src"    => $src,
			"alt"    => $alt,
			"id"     => $id,
			"output" => false,
		));
	}


	function html($lang, $class = null)
	{
		return Tag::tag('html', array(
			"class"  => $class,
			"lang"   => $lang,
			"output" => false,
		));
	}


	function head($content = null)
	{
		return Tag::tag('html', array(
			"content" => $content,
			"output" => false,
		));
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
	/** @alias System\Http\Response::redirect */
	function redirect_now($url, $code=\System\Http\Response::FOUND) {
		return System\Http\Response::redirect($url, $code);
	}


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

	/** @alias System\Output::slot */
	function slot($name = System\Template::DEFAULT_SLOT) {
		return System\Output::slot($name);
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

	/** @alias System\Template::to_json() */
	function to_json($value, $encode = true)
	{
		return \System\Template::to_json($value, $encode);
	}
}
