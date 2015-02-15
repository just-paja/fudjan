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
			"group"   => array("type" => 'belongs_to', "model" => 'System\User\Group'),
			"name"    => array("type" => 'varchar'),
			"trigger" => array("type" => 'varchar'),
			"public"  => array("type" => 'bool'),
			"allow"   => array("type" => 'bool')
		);
	}
}
