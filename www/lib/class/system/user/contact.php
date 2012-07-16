<?

namespace System\User
{
	class Contact extends \System\Model\Basic
	{
		static protected $id_col = 'id_user_contact';
		static protected $table = 'user_contact';
		static protected $required = array('ident');
		static protected $attrs = array(
			"int"    => array('id_user', 'id_contact_type'),
			"string" => array('name', 'ident'),
			"bool"   => array('visible', 'deleted'),
			"datetime" => array('created_at', 'updated_at'),
		);

		static protected $belongs_to = array(
			"user" => array("model" => '\System\User'),
		);

		static private $internal_types = array(
			'email'  => self::TYPE_EMAIL,
			'mobile' => self::TYPE_MOBILE,
		);

		const STD_EMAIL   = 1;
		const STD_MOBILE  = 2;
		const STD_WEBSITE = 3;
		const STD_PHONE   = 4;

		const IM_XMPP_JABBER = 5;
		const IM_XMPP_GTALK  = 6;
		const IM_AIM   = 7;
		const IM_ICQ   = 8;
		const IM_MSN   = 9;
		const IM_YAHOO = 10;
	}
}
