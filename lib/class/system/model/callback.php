<?php

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


		/** Callbacks container */
		protected static $callbacks = array(
			self::BEFORE_SAVE   => array(),
			self::BEFORE_DELETE => array(),
			self::AFTER_SAVE    => array(),
			self::AFTER_DELETE  => array(),
		);


		/** Run callbacks
		 * @param string $trigger Name of trigger to fier
		 * @param array  $args    Data to pass
		 * @return void
		 */
		public function run_tasks($trigger, array $args = array())
		{
			if (any($this::$callbacks[$trigger])) {
				foreach ($this::$callbacks[$trigger] as $task) {
					if (is_callable(array($this, $task))) {
						$this->$task($args);
					} else throw new \System\Error\Model(sprintf('Invalid callback "%s" for "%s"', $task, $trigger));
				}
			}
		}
	}
}
