<?

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
	class Text extends \System\Model\Database
	{
		/** Attributes */
		static $attrs = array(
			"name"    => array('varchar'),
			"text"    => array('text'),
			"visible" => array('bool'),
		);
	}
}
