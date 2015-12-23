<?php


namespace Test\Cache
{
	class Core extends \PHPUnit_Framework_TestCase
	{
		public function test_core_building()
		{
			\System\Cache::build_core();

			$dir = BASE_DIR.'/var/cache/runtime';
			$cmd = implode(';', array(
				'cd '.$dir,
				'php core.php'
			));

			$out  = '';
			$stat = 0;

			exec($cmd, $out, $stat);

			$this->assertEquals('', implode('', $out));
			$this->assertEquals(0, $stat);

			unlink($dir.'/core.php');
		}
	}
}
