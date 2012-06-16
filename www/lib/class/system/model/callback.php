<?

namespace System\Model
{
	abstract class Callback extends Attr
	{
		// Model callbacks
		protected $before_save = array();
		protected $after_save  = array();
		protected $before_delete = array();
		protected $after_delete = array();


		/** Add a callback function to some action
		 * @param string  $trigger One of triggers
		 * @param Closure $lambda  Instance of closure
		 * @param array   $data    Data to be passed to the lambda function
		 */
		public function add_callback($trigger, Closure $lambda, array $data = array())
		{
			if (in_array($trigger, array("before_save", "before_delete", "after_save", "after_delete")))
			{
				$target = &$this->$trigger;
				$target[] = array($lambda, $data);
			}
			return $this;
		}


		/** Run callbacks
		 * @param array Set of callbacks
		 * @returns void
		 */
		private static function run_tasks(self $obj, $tasks)
		{
			foreach ($tasks as $task) {
				$lambdaf = $task[0];
				$lambdaf($obj, $task[1]);
			}
		}
	}
}
