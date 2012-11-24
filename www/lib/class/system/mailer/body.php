<?

namespace System\Mailer
{
	class Body extends \System\Model\Database
	{
		static protected $attrs = array(
			"id_mailer" => array('int', "is_unsigned" => true),
			"type"      => array('varchar'),
			"subject"   => array('varchar'),
			"rcpt"      => array('varchar'),
			"from"      => array('varchar'),
			"reply_to"  => array('varchar'),
			"cc"        => array('varchar'),
			"bcc"       => array('varchar'),
			"template_name"  => array('varchar'),
			"visible"   => array('bool'),
		);

		static protected $belongs_to = array(
			"mailer" => array("model" => '\Core\Mailer'),
		);

		static protected $has_many = array(
			"queued_items" => array("model" => '\Core\Mailer\QueueItem'),
		);


		static private $types = array(
			"email" => 'E-mail',
			"sms"   => 'SMS',
		);


		static public function get_types()
		{
			return self::$types;
		}


		public function get_template()
		{
			if ($this->template_name && file_exists($p = ROOT.\Core\Mailer::TEMPLATE_DIR.'/'.$this->template_name)) {
				return file_get_contents($p);
			}
		}
	}
}
