<?

namespace System
{
	class Text extends \System\Model\Basic
	{
		static $attrs = array(
			"string" => array('name', 'lang', 'text'),
			"datetime" => array('created_at', 'updated_at'),
			"bool" => array('public'),
		);
	}
}
