<?

// BasicModel & ExtModel aliases
function get_all($model, array $conds = array(), array $opts = array(), array $joins = array()) { return System\Model\Ext::get_all($model, $conds, $opts, $joins); }
function get_first($model, array $conds = array(), array $opts = array(), array $joins = array()) { return System\Model\Ext::get_first($model, $conds, $opts, $joins); }
function get_tree($model, array $conds = array(), array $opts = array()) { return System\NestedModel::get_tree($model, $conds, $opts); }
function count_all($model, array $conds = array(), array $opts = array(), array $joins = array()) { return System\Model\Database::count_all($model, $conds, $opts, $joins); }
function find($model, $ids = array(), $force_array = false) { return System\Model\Ext::find($model, $ids, $force_array); }
function create($model, array $dataray) { $item = new $model($dataray); return $item->save(); }
function quick_get_all($model) { return System\Model\Database::get_all($model, quick_conds($model)); }
function quick_conds($model) { return System\Model\Database::get_quick_conds($model); }

// Messages
function message($status, $title, $message=null, $autohide=false, $links = array()) { $msg = new System\Message($status, $title, $message, $autohide, $links, false); return $msg->get_retval(); }
function t_message($status, $title, $message=null, $autohide=false, $links = array())
{
	$msg = new System\Message($status, $title, $message, $autohide, $links, false);
	System\Template::partial("system/message", array("message" => $msg));
	return $msg->get_retval();
}


// Redirects
function redirect_now($url) { return System\Flow::redirect_now($url); }
function redirect($url, array $opts = array()) { return System\Flow::redirect($url, $opts); }
function trigger($name, array $data, $immediate = false){ System\Mailer::trigger($name, $data, $immediate); }


// Template
function link_for($label, $url=null, $object=array()){ return System\Template::link_for($label, $url, $object); }
function icon_for($icon, $size=32, $url, $label = NULL, $object = array()){ return System\Template::icon_for($icon, $size, $url, $label, $object); }
function label_for($icon, $size=32, $label, $url, $object = array()) { $object['label'] = true; return System\Template::icon_for($icon, $size, $url, $label, $object); }
function icon($icon, $size=32, array $attrs = array()){ return System\Template::icon($icon, $size, $attrs); }
function format_time($datetime, $format){ return System\Template::format_time($datetime, $format); }
function heading($label, $save_level = true, $level = NULL){ return System\Template::heading($label, $save_level, $level); }
function section_heading($label, $level = NULL){ return System\Template::section_heading($label, $level); }
function html_attrs($tag, $attrs){ return System\Template\Tag::html_attrs($tag, $attrs); }
function content_for($place, $content, $overwrite = false) { return System\Output::content_for($place, $content, $overwrite); }
function content_from($place) { return System\Output::content_from($place); }
function slot($name = System\Template::DEFAULT_SLOT) { return System\Output::slot($name); }
function yield() { return System\Output::yield(); }
function title() { return System\Output::get_title(true); }
function path() { return System\Page::get_path(); }
function get_css_color($color) { return System\Template::get_css_color($color); }
function get_color_container($color) { return System\Template::get_color_container($color); }
class Tag extends System\Template\Tag {}


// Miscelanous
function user() { return System\User::get_active(); }
function logged_in() { return System\User::logged_in(); }
function strlen_binary($str) { return System\Locales::strlen_binary($str); }


// Arrays
function collect_ids(array $array){ return collect(array('attr', 'id'), $array); }
function collect_names(array $array){ return collect(array('this', 'get_name'), $array); }


// Locales
function translate_date($str, $hard = false) { return System\Locales::translate_date($str, $hard); }
function sysmsg($errors) { return System\Locales::sysmsg($errors); }
function l($str, $lang = null) { return System\Locales::translate($str, $lang); }
function t($str) { return System\Locales::translate_and_replace($str, func_get_args()); }


// Config
function cfg() { return System\Settings::get(func_get_args()); }
function cfgs(array $path, $value) { return System\Settings::set($path, $value); }
