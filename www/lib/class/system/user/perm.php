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
			"id_system_user_group" => array('int', "is_unsigned" => true),
			"id_author"     => array('int', "is_unsigned" => true),
			"type"          => array('varchar'),
			"trigger"       => array('varchar'),
			"public"        => array('bool'),
		);


		/** Model belongs to relations
		 * @param array
		 */
		static protected $belongs_to = array(
			"group" => array("model" => '\System\User\Group'),
		);
	}
}
