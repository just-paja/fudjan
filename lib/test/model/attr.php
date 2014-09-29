<?


namespace Test\Model
{
	class Attr extends \System\Model\Attr
	{
		protected static $attrs = array(
		);
	}
}


namespace
{
	class Attr extends PHPUnit_Framework_TestCase
	{
		public function test_constructor()
		{
			$obj = new \Test\Model\Attr();
		}
	}
}
