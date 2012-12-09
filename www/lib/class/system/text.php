<?

namespace System
{
	class Text extends \System\Model\Database
	{
		static $attrs = array(
			"name"    => array('varchar'),
			"text"    => array('text'),
			"visible" => array('bool'),
		);
	}
}
