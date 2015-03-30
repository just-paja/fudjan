<?

namespace Test\Model
{
	class All extends \PHPUnit_Framework_TestCase
	{
		/**
		 * @dataProvider model_batch
		 */
		public function test_models($cname)
		{
			$cname::check_model();
		}


		/**
		 * @dataProvider model_relations_batch
		 */
		public function test_model_relations($cname, $rel)
		{
			$this->assertTrue(array_key_exists('model', $rel));
			$this->assertTrue(class_exists($rel['model']));
		}


		public static function model_relations_batch()
		{
			$list = static::get_children_of('System\Model\Database');
			$data = array();

			foreach ($list as $cname) {
				$rels = $cname::get_model_relations();

				foreach ($rels as $rel) {
					$data[] = array($cname, $rel);
				}
			}

			return $data;
		}


		public static function model_batch()
		{
			$list = static::get_children_of('System\Model\Attr');
			$data = array();

			foreach ($list as $cname) {
				$data[] = array($cname);
			}

			return $data;
		}


		public static function get_children_of($parent)
		{
			\System\Loader::load_all();

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
