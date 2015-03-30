<?

namespace Test\Offcom
{
	class Mail extends \PHPUnit_Framework_TestCase
	{
		/**
		 * @dataProvider valid_batch
		 */
		public function test_address_validation($addr)
		{
			$this->assertTrue(\Helper\Offcom\Mail::is_addr_valid($addr));
		}


		/**
		 * @dataProvider invalid_batch
		 */
		public function test_address_invalidation($addr)
		{
			$this->assertFalse(\Helper\Offcom\Mail::is_addr_valid($addr, true));
		}


		public function valid_batch()
		{
			return array(
				array('asdfg.hjklmnb@hotmail.com'),
				array('a@hotmail.com'),
				array('z.x@hotmail.com'),
				array('z_x@123.cz'),
				array('asdfa_6.d5f46a-s54df6asdf@yu.cz'),
				array('aa@bb.cc'),
				array('aa@bb.cc, dd@ee.ff'),
			);
		}


		public function invalid_batch()
		{
			return array(
				array('aa@bb.cc, d@e.f'),
				array('a@b.c, dd@ee.ff'),
				array('a@b.c, d@e.f'),
				array('ab.c'),
				array(''),
				array(null),
				array('>asd@tt.cz'),
				array('/@tt.cz'),
			);
		}
	}
}
