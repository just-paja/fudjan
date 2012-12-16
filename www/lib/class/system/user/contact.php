<?

namespace System\User
{
	class Contact extends \System\Model\Database
	{
		static protected $attrs = array(
			"id_contact_type" => array('int', "is_unsigned" => true),
			"name"            => array('varchar'),
			"ident"           => array('varchar'),
			"visible"         => array('bool'),
			"deleted"         => array('bool'),
		);

		static protected $belongs_to = array(
			"user" => array("model" => '\System\User', "is_natural" => true),
		);

		static private $internal_types = array(
			'email'  => self::STD_EMAIL,
			'mobile' => self::STD_MOBILE,
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
