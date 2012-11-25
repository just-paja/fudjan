<?

namespace System\Form
{
	class Label extends \System\Form\Element
	{
		protected static $attrs = array(
			"id"      => array("varchar"),
			"class"   => array("varchar"),
			"for"     => array("varchar"),
			"content" => array("varchar"),
		);
	}
}
