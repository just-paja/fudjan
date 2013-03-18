<?

namespace System\Santa\Package
{
	class Version extends \System\Model\Attr
	{
		protected static $attrs = array(
			"name"    => array('varchar'),
			"repo"    => array('varchar'),
			"branch"  => array('varchar'),
			"package" => array('varchar'),
		);
	}
}
