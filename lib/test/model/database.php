<?


namespace Test\Model
{
	class Database extends \System\Model\Database
	{
		protected static $attrs = array(
			'int_blank' => array('int'),
			'int_nil'   => array('int', 'is_null' => true),
			'int_def'   => array('int', 'default' => 5),
		);


		public function save()
		{
			$this->id = 1;
			return $this;
		}
	}
}


namespace
{
	class Database extends PHPUnit_Framework_TestCase
	{
		public function test_lifecycle()
		{
			$obj = new \Test\Model\Database();

			$this->assertTrue($obj->is_new());
			$obj->save();
			$this->assertFalse($obj->is_new());
		}
	}
}
