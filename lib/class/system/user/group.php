<?

namespace System\User
{
	class Group extends \System\Model\Database
	{
		static protected $attrs = array(
			"name"   => array('varchar'),
			"users"  => array('has_many', "model" => 'System\User', "is_bilinear" => true),
			"rights" => array('has_many', "model" => 'System\User\Perm'),
		);


		public function count_users()
		{
			return $this->users->count();
		}


		public function to_html(\System\Template\Renderer $ren)
		{
			return $this->name;
		}
	}
}
