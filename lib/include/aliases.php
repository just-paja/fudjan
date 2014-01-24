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
		return Tag::tag('body', array(
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
	 * @param string|array $class   Classname passed to the span
	 * @param string|array $content Content rendered inside span
	 * @param string       $id      ID attribute of the span
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
	 * @param string|array $class   Classname passed to the ul
	 * @param string|array $content Content rendered inside ul
	 * @param string       $id      ID attribute of the ul
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


	/** OL tag alias
	 * @param string|array $class   Classname passed to the ul
	 * @param string|array $content Content rendered inside ul
	 * @param string       $id      ID attribute of the ul
	 * @return string
	 */
	function ol($class, $content = null, $id = null)
	{
		return Tag::tag('ol', array(
			"class"   => $class,
			"content" => $content,
			"id"      => $id,
			"output"  => false,
		));
	}


	/** P tag alias
	 * @param string|array $content Content rendered inside p
	 * @param string|array $class   Classname passed to the p
	 * @param string       $id      ID attribute of the p
	 * @return string
	 */
	function p($content = null, $class = null, $id = null)
	{
		return Tag::tag('p', array(
			"class"   => $class,
			"content" => $content,
			"id"      => $id,
			"output"  => false,
		));
	}


	/** Menu tag alias
	 * @param string|array $class   Classname passed to the menu
	 * @param string|array $content Content rendered inside menu
	 * @param string       $id      ID attribute of the menu
	 * @return string
	 */
	function menu($class, $content = null, $id = null)
	{
		return Tag::tag('menu', array(
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
		return Tag::tag('head', array(
			"content" => $content,
			"output" => false,
		));
	}


	function table($class = null, $content = null)
	{
		return Tag::tag('table', array(
			"content"     => $content,
			"class"       => $class,
			"cellspacing" => 0,
			"cellpadding" => 0,
			"output"      => false
		));
	}


	function tbody($class = null, $content = null)
	{
		return Tag::tag('tbody', array(
			"content" => $content,
			"class"   => $class,
			"output"  => false
		));
	}


	function thead($class = null, $content = null)
	{
		return Tag::tag('thead', array(
			"content" => $content,
			"class"   => $class,
			"output"  => false
		));
	}


	function th($class = null, $content = null)
	{
		return Tag::tag('th', array(
			"content" => $content,
			"class"   => $class,
			"output"  => false
		));
	}


	function td($class = null, $content = null)
	{
		return Tag::tag('td', array(
			"content" => $content,
			"class"   => $class,
			"output"  => false
		));
	}


	function tr($class = null, $content = null)
	{
		return Tag::tag('tr', array(
			"content" => $content,
			"class"   => $class,
			"output"  => false
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


	// Redirects
	/** @alias System\Http\Response::redirect */
	function redirect_now($url, $code=\System\Http\Response::FOUND) {
		return System\Http\Response::redirect($url, $code);
	}


	// Template
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

	/** @alias System\Template::to_html() */
	function to_html(\System\Template\Renderer $ren, $value)
	{
		return \System\Template::to_html($ren, $value);
	}
}
