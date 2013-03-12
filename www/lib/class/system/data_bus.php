<?

namespace System
{
	/** Handles communication among module instances
	 * @used-by \System\Module
	 */
	abstract class DataBus
	{
		static private $data = array();
		static private $modules = array();

		/** Get data by path. You can pass array or any number of string arguments describing path to data.
		 */
		public static function get_data()
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


		/** Save data into DataBus and associate it with module ID for later use
		 * @param \System\Module $module
		 * @param mixed $data
		 */
		public static function save_data(\System\Module &$module, &$data)
		{
			self::$data[$module->get_id()] = &$data;
		}
	}
}
