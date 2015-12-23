<?php

namespace Test\Module
{
	class All extends \PHPUnit_Framework_TestCase
	{
		public function test_load()
		{
			\System\Loader::load_all_modules();
		}


		/**
		 * @dataProvider module_batch
		 */
		public function test_module_syntax($cname)
		{
			$this->assertTrue(method_exists($cname, 'run'));
		}


		public static function module_batch()
		{
			\System\Loader::load_all_modules();

			$list = static::get_children_of('System\Module');
			$data = array();

			foreach ($list as $cname) {
				$data[] = array($cname);
			}

			return $data;
		}


		public static function get_children_of($parent)
		{
			$list = array();
			$all  = get_declared_classes();

			foreach ($all as $cname) {
				if (in_array($parent, class_parents($cname))) {
					$list[] = $cname;
				}
			}


			return $list;
		}
	}
}
