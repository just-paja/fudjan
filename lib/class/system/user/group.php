<?php

namespace System\User
{
	class Group extends \System\Model\Perm
	{
		static protected $attrs = array(
			"name"   => array("type" => 'varchar'),
			"users"  => array("type" => 'has_many', "model" => 'System\User', "is_bilinear" => true),
			"rights" => array("type" => 'has_many', "model" => 'System\User\Perm'),
		);

		static protected $access = array(
			'schema' => true,
			'browse' => true
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
