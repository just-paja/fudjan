<?

namespace System\Module
{
	/** Handles communication among module instances
	 * @used-by \System\Module
	 */
	class DataBus
	{
		private $flow;
		private $data = array();
		private $modules = array();


		/** Public constructor
		 * @returns $this
		 */
		public function __construct(\System\Module\Flow $flow)
		{
			$this->flow = $flow;
		}


		/** Get data by path. You can pass array or any number of string arguments describing path to data.
		 * @return array
		 */
		public function get_data()
		{
			$sources = func_get_args();

			if (is_array($sources[0])) {
				$sources = $sources[0];
			}

			$ret = array();

			foreach ($sources as $s) {
				if (isset($this->data[$s])) {
					$ret = array_merge($ret, (array) $this->data[$s]);
				}
			}

			return $ret;
		}


		/** Add data to dbus
		 * @param \System\Module $module Parent module
		 * @param string         $name   Data name
		 * @param mixed          $data   Data to save
		 * @return $this
		 */
		public function add_data(\System\Module $module, $name, $data)
		{
			if (!isset($this->data[$module->module_id])) {
				$this->data[$module->module_id] = array();
			}

			$this->data[$module->module_id][$name] = $data;
			return $this;
		}
	}
}
