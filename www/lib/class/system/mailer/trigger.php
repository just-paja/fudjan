<?

namespace System\Mailer
{
	class Trigger extends \System\Model\Basic
	{
		static protected $id_col = 'id_mailer_trigger';
		static protected $table  = 'mailer_trigger';
		static protected $required_attrs = array('name', 'trigger_name');
		static protected $attrs = array(
			"int"      => array('used'),
			"string"   => array('name', 'trigger_name'),
			"bool"     => array('used', 'deleted'),
			"datetime" => array('created_at', 'updated_at'),
		);

		static protected $belongs_to = array(
			"mailer" => array("model" => '\Core\Mailer'),
		);

		static protected $has_many = array(
			"bodies" => array("model" => '\Core\Mailer\Body'),
		);


		public static function fire($name, array $data, $immediate = false)
		{
			$user = user()->get_data();                                     // replace current user's data
			$mailer = get_first("\Core\Mailer", array("trigger_name" => $name))->fetch();
			$data['server'] = $_SERVER;

			if ($mailer && $mailer->used) {
				if (isset($data['user']) && ($data['user'] instanceof User)) {
					$send = $data['user']->get_mailer_types();
				} else {
					$send = array('email');
				}

				foreach ($send as $type) {
					$bname = 'body_'.$type;
					$body = $mailer->$bname;
					$template = $body->get_template();
					$headers = array(
						"Subject"  => $body->subject,
						"To"       => $body->rcpt,
						"From"     => $body->from,
						"Reply-To" => $body->reply_to,
						"CC"       => $body->cc,
						"BCC"      => $body->bcc,
					);

					foreach ($data as $prefix=>$d) {

						$strict = $prefix == 'server';

						foreach ($headers as &$h) {
							$h = $d instanceof \Core\System\BasicModel ? soprintf($h, $d, $strict, $prefix):stprintf($h, $d, $strict, $prefix);
						}

						$template = $d instanceof \Core\System\BasicModel ? soprintf($template, $d, $strict, $prefix):stprintf($template, $d, $strict, $prefix);
					}

					$hs = array();

					foreach ($headers as $key=>$val) {
						if ($val) {
							$hs[] = $key.": ".$val;
						}
					}

					$q = new Mailer\QueueItem(array(
						"id_mailer" => $mailer->id,
						"id_mailer_body" => $body->id,
						"id_user_invoker" => User::get_active()->id,
						"headers" => implode("\n", $hs),
						"body" => $template,
						"status" => 'ready'
					));
					$q->save();
					
					if ($immediate) {
						$q->send();
					}
				}
			}
		}
	}
}
