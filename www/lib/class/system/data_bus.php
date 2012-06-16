<?

namespace System
{
	abstract class DataBus
	{
		static private $data = array();
		static private $modules = array();

		static function get_data()
		{
			$sources = func_get_args();

			if (is_array($sources[0])) {
				$sources = $sources[0];
			}

			$ret = array();

			foreach ($sources as $s) {
				if (isset(self::$data[$s])) {
					$ret = array_merge($ret, (array) self::$data[$s]);
				}
			}

			return $ret;
		}


		static function save_data(Module &$module, &$data)
		{
			self::$data[$module->get_id()] = &$data;
		}
	}
}
