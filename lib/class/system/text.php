<?php

/** Static texts
 * @package system
 * @subpackage models
 */
namespace System
{
	/** Static text
	 * @package system
	 * @subpackage models
	 */
	class Text extends \System\Model\Perm
	{
		/** Attributes */
		static $attrs = array(
			"ident"   => array("type" => 'varchar'),
			"name"    => array("type" => 'varchar'),
			"text"    => array("type" => 'html'),
			"author"  => array("type" => 'belongs_to', "model" => 'System\User'),
			"visible" => array("type" => 'bool'),
		);
	}
}
