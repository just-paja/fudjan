<?

namespace System
{
	class Text extends \System\Model\Database
	{
		static $attrs = array(
			"name"   => array('varchar'),
			"lang"   => array('varchar'),
			"text"   => array('text'),
			"public" => array('bool'),
		);
	}
}
