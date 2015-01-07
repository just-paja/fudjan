<?


namespace
{
	class Core extends PHPUnit_Framework_TestCase
	{
		public function test_core_building()
		{
			\System\Cache::build_core();

			$cmd = implode(';', array(
				'cd '.BASE_DIR.'/var/cache',
				'php core.php'
			));

			$out  = '';
			$stat = 0;

			exec($cmd, $out, $stat);

			$this->assertEquals('', implode('', $out));
			$this->assertEquals(0, $stat);
		}
	}
}
