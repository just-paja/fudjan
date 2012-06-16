<?

namespace Core\Mailer;

class Body extends \Core\System\BasicModel
{
	static protected $id_col = 'id_mailer_body';
	static protected $table  = 'mailer-body';
	static protected $required_attrs = array('type');
	static protected $attrs = array(
		"int"      => array('id_mailer'),
		"string"   => array('type', 'subject', 'rcpt', 'from', 'reply_to', 'cc', 'bcc', 'template_name'),
		"bool"     => array('visible'),
		"datetime" => array('created_at', 'updated_at'),
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
