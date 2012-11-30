<?

namespace System\Form
{
	class Text extends \System\Form\Element
	{
		protected static $attrs = array(
			"id"      => array("varchar"),
			"class"   => array("varchar"),
			"label"   => array("varchar"),
			"content" => array("varchar"),
		);
	}
}
