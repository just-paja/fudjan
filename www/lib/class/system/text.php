<?

namespace System
{
	class Text extends \System\Model\Database
	{
		static $attrs = array(
			"string" => array('name', 'lang', 'text'),
			"datetime" => array('created_at', 'updated_at'),
			"bool" => array('public'),
		);
	}
}
