<?

namespace System
{
	class Mailer extends Model\Basic
	{
		const TEMPLATE_DIR = "/lib/template/mailer";

		static protected $id_col = 'id_mailer';
		static protected $table  = 'mailer';
		static protected $required_attrs = array('name', 'trigger_name');
		static protected $attrs = array(
			"int"      => array('run_count'),
			"string"   => array('name', 'trigger_name'),
			"bool"     => array('used', 'deleted'),
			"datetime" => array('created_at', 'updated_at'),
		);


		static protected $has_many = array(
			"bodies" => array("model" => '\Core\Mailer\Body'),
		);


		static protected $has_one = array(
			"body_email" => array("model" => '\Core\Mailer\Body', "conds" => array("type" => 'email')),
			"body_sms" => array("model" => '\Core\Mailer\Body', "conds" => array("type" => 'sms')),
		);


		public static function get_all_templates($type = null)
		{
			$templates = array();
			$dir = opendir($p = ROOT.self::TEMPLATE_DIR);

			while ($f = readdir($dir)) {
				if ((is_file($p.'/'.$f) && (strpos($f, ".html") || strpos($f, ".txt"))) && (!$type || ($type && strpos($f, ".".$type.".")))) {
					$templates[] = $f;
				}
			}

			return $templates;
		}


		public static function trigger($name, array $data, $immediate = false)
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


		static private function get_run_stack()
		{
			return get_all("Core\Mailer\QueueItem", array("status" => 'ready'), array("order-by" => 'created_at ASC', "limit" => 256))->fetch();
		}


		// send all queued messages
		static public function run_queue()
		{
			$status = array();
			if (true || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || user()->has_right('run-cron')) {
				if (!System\Settings::get('offcom', 'disable')) {
					$items = self::get_run_stack();

					while (any($items)) {

						foreach ($items as &$item) {
							$status[$item->id] = $item->send();
						}

						break;
					}

					message('success', _('Rozesílání fronty zpráv'), _('Rozesílání e-mailové fronty dokončeno'));
				} else message('error', _('Rozesílání fronty zpráv'), _('Síťová komunikace je zakázána'));
			} else message('error', _('Rozesílání fronty zpráv'), _('Nemáte oprávnění k rozesílání e-mailové fronty'));
			
			return $status;
		}
	}
}
