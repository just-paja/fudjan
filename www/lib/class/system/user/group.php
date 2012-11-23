<?

namespace System\User
{
	class Group extends \System\Model\Database
	{
		static protected $attrs = array(
			"name"   => array('varchar'),
		);

		static protected $has_many = array(
			"users"  => array("model" => '\System\User', "join-table" => 'user_group_assignment'),
			"rights" => array("model" => '\System\User\Perm'),
		);
	}
}
