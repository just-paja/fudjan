<?

namespace
{
	class Mail extends PHPUnit_Framework_TestCase
	{
		/**
		 * @dataProvider additionProvider
		 */
		public function test_address_validation($addr)
		{
			$this->assertTrue(\System\Offcom\Mail::is_addr_valid($addr));
		}


		public function additionProvider()
		{
			return array(
				array('asdfg.hjklmnb@hotmail.com'),
				array('a@hotmail.com'),
				array('z.x@hotmail.com'),
				array('z_x@123.cz'),
				array('asdfa_6.d5f46a-s54df6asdf@yu.cz'),
			);
		}
	}
}
