<?

namespace System
{
	class Location extends \System\Model\Database
	{
		protected static $attrs = array(
			"name" => array("varchar"),
			"addr" => array("varchar"),
			"lon"  => array("varchar"),
			"lat"  => array("varchar"),
			"desc" => array("text"),
			"site" => array("url"),
		);


		protected static $belongs_to = array(
			"user" => array("model" => '\System\User'),
		);
	}
}
