<?


namespace Test\Model
{
	class Attr extends \System\Model\Attr
	{
		protected static $attrs = array(
			'int_blank' => array('int'),
			'int_nil'   => array('int', 'is_null' => true),
			'int_def'   => array('int', 'default' => 5),
		);
	}
}


namespace
{
	class Attr extends PHPUnit_Framework_TestCase
	{
		public function test_attr_existance()
		{
			$obj = new \Test\Model\Attr();

			$this->assertFalse(false, $obj->has_attr('bad'));
			$this->assertFalse(false, \Test\Model\Attr::has_attr('bad'));

			$this->assertTrue($obj->has_attr('int_nil'));
			$this->assertTrue(\Test\Model\Attr::has_attr('int_nil'));

			$this->assertTrue($obj->has_attr('int_blank'));
			$this->assertTrue($obj->has_attr('int_def'));
		}


		public function test_int_attr()
		{
			$obj = new \Test\Model\Attr();

			$this->assertNull($obj->int_nil);
			$this->assertNull($obj->int_blank);
			$this->assertEquals(5, $obj->int_def);

			$obj->int_blank = 'asdf';
			$obj->int_nil   = 'asdf';
			$obj->int_def   = 'asdf';

			$this->assertEquals(0, $obj->int_blank);
			$this->assertNull($obj->int_nil);
			$this->assertEquals(5, $obj->int_def);

		}
	}
}
