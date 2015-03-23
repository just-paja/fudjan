<?

namespace
{
	class All extends PHPUnit_Framework_TestCase
	{
		/**
		 * @dataProvider model_batch
		 */
		public function test_models($cname)
		{
			$cname::check_model();
		}


		public function model_batch()
		{
			\System\Loader::load_all();

			$list = array();
			$all  = get_declared_classes();

			foreach ($all as $cname) {
				if (in_array('System\Model\Attr', class_parents($cname))) {
					$list[] = array($cname);
				}
			}

			return $list;
		}
	}
}
