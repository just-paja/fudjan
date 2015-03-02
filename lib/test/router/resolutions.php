<?

namespace
{
	class Resolutions extends PHPUnit_Framework_TestCase
	{
		/**
		 */
		public function test_domain_match()
		{
			$global = array(
				'rules' => array(
					".*"
				)
			);

			$loc = array(
				'rules' => array(
					"localhost$"
				)
			);

			$this->assertTrue(\System\Router::domain_match('http://localhost', $global));
			$this->assertTrue(\System\Router::domain_match('http://localhost.com', $global));
			$this->assertTrue(\System\Router::domain_match(null, $global));

			$this->assertTrue(\System\Router::domain_match('http://localhost', $loc));
			$this->assertTrue(\System\Router::domain_match('http://jebka.localhost', $loc));
			$this->assertFalse(\System\Router::domain_match(null, $loc));
			$this->assertFalse(\System\Router::domain_match('foo.bar', $loc));
			$this->assertFalse(\System\Router::domain_match('jebka.localhost.com', $loc));
		}


		public function test_is_domain()
		{
			$cfg = \System\Settings::get('domains');

			foreach ($cfg as $key=>$def) {
				$this->assertTrue(\System\Router::is_domain($key));
			}

			$this->assertFalse(\System\Router::is_domain('Stupid-Domain-Name'));
		}



	}
}
