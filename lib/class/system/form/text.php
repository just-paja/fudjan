<?

namespace System\Form
{
	class Text extends \System\Form\Element
	{
		protected static $attrs = array(
			"id"      => array("type" => "varchar"),
			"class"   => array("type" => "varchar"),
			"label"   => array("type" => "varchar"),
			"content" => array("type" => "text"),
		);
	}
}
