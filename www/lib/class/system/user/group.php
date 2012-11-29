<?

namespace System\User
{
	class Group extends \System\Model\Database
	{
		static protected $attrs = array(
			"name"   => array('varchar'),
		);

		static protected $has_many = array(
			"users"  => array("model" => '\System\User', "is_bilinear" => true),
			"rights" => array("model" => '\System\User\Perm'),
		);


		public function count_users()
		{
			return $this->users->count();
		}
	}
}
