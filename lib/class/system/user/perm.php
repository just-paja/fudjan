<?

/** User permissions model
 * @package system
 * @subpackage users
 */
namespace System\User
{
	/** User permissions model
	 * @package system
	 * @subpackage users
	 * @property array $attrs
	 */
	class Perm extends \System\Model\Database
	{
		/** Model attributes
		 * @param array
		 */
		static protected $attrs = array(
			"group"     => array('belongs_to', "model" => '\System\User\Group'),
			"type"      => array('varchar'),
			"trigger"   => array('varchar'),
			"public"    => array('bool'),
		);
	}
}
