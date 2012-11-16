<?

namespace System\User
{
	class Group extends \System\Model\Basic
	{
		static protected $required = array('name');
		static protected $attrs = array(
			"string"   => array('name'),
			"datetime" => array('created_at','updated_at')
		);

		static protected $has_many = array(
			"users"  => array("model" => '\System\User', "join-table" => 'user_group_assignment'),
			"rights" => array("model" => '\System\User\Perm'),
		);
	}
}
