<?

/** Model callback handling
 * @package system
 * @subpackage models
 */
namespace System\Model
{
	/** Gives child classes ability to use callbacks. Very useful if you do
	 * something every time befor or after saving object into database
	 * @package system
	 * @subpackage models
	 */
	abstract class Callback extends Attr
	{
		// Model callbacks
		const BEFORE_SAVE   = 'before_save';
		const BEFORE_DELETE = 'before_delete';
		const AFTER_SAVE    = 'after_save';
		const AFTER_DELETE  = 'after_delete';

		private static $callbacks = array(
			self::BEFORE_SAVE   => array(),
			self::BEFORE_DELETE => array(),
			self::AFTER_SAVE    => array(),
			self::AFTER_DELETE  => array(),
		);


		/** Add a callback function to some action
		 * @param string  $trigger One of triggers
		 * @param Closure $lambda  Instance of closure
		 * @param array   $data    Data to be passed to the lambda function
		 */
		public function add_callback($trigger, Closure $lambda)
		{
			if (array_key_exists($trigger, self::$callbacks))
			{
				$model = get_class($this);

				if (!isset(self::$callbacks[$trigger][$model])) {
					self::$callbacks[$trigger][$model] = array();
				}

				self::$callbacks[$trigger][$model][] = $lambda;
			}
			return $this;
		}


		/** Run callbacks
		 * @param array Set of callbacks
		 * @return void
		 */
		public function run_tasks($trigger, array $args = array())
		{
			$model = get_class($this);

			if (any(self::$callbacks[$trigger][$model])) {
				foreach (self::$callbacks[$trigger][$model] as $task) {
					$task($this, $args);
				}
			}
		}
	}
}
