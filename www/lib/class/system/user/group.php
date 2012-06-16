<?

namespace System\User
{
	class Group extends \System\Model\Basic
	{
		static protected $id_col = 'id_user_group';
		static protected $table = 'user-group';
		static protected $required = array('name');
		static protected $attrs = array(
			"string"   => array('name'),
			"datetime" => array('created_at','updated_at')
		);

		static protected $has_many = array(
			"users"  => array("model" => '\System\User', "join-table" => 'user-group-assignment'),
			"rights" => array("model" => '\System\User\Perm'),
		);
	}
}
