<?php


/** Module that displays partial without locals
 * @package modules
 */
namespace Module\System
{
	class Partial extends \System\Module
	{
		public function run()
		{
			$this->req('template');
			$this->partial($this->template);
		}
	}
}
