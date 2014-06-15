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
	class Text extends \System\Model\Perm
	{
		/** Attributes */
		static $attrs = array(
			"ident"   => array('varchar'),
			"name"    => array('varchar'),
			"text"    => array('html'),
			"author"  => array('belongs_to', "model" => 'System\User'),
			"visible" => array('bool'),
		);
	}
}
