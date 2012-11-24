<?

namespace System\Mailer
{
	class QueueItem extends \System\Model\Database
	{

		static protected $attrs = array(
			"id_mailer"       => array('int', "id_unsigned" => true),
			"id_mailer_body"  => array('int', "id_unsigned" => true),
			"id_user_invoker" => array('int', "id_unsigned" => true),
			"headers"         => array('text'),
			"body"            => array('text'),
			"status"          => array('varchar'),
			"return_message"  => array('varchar'),
		);


		static protected $belongs_to = array(
			"mailer" => array("model" => '\Core\Mailer'),
			"template_body"   => array("model" => '\Core\Mailer\Body', "cols" => array('type'), "merge-model" => true),
		);


		static private $states = array(
			"ready"   => 'Připraveno k odeslání',
			"sending" => 'Odesílá se',
			"sent"    => 'Odesláno',
			"failed"  => 'Selhalo',
		);


		static public function get_all_states()
		{
			return $states;
		}


		// send message to a gateway or mailserver
		public function send()
		{
			if (!\Core\System\Settings::get('dev', 'disable-offcom')) {
				if ($this->type == 'email') {
					$hs = explode("\n", $this->headers);

					foreach ($hs as $row) {
						list($k, $w) = explode(": ", $row, 2);
						$headers[$k] = trim($w);
					}

					$to = $headers['To'];
					unset($headers['To']);

					$mail = \Core\Offcom\Mail::create(explode(',', $to), $headers, $this->body);
					$this->status = $mail->send() == \Core\System\Model\Offcom::STATUS_SENT ? 'sent':'error';
					$this->save();

					return $this->status;
				} else {
					// not implemented
				}
			} else message('error', _('Rozesílání fronty zpráv'), _('Síťová komunikace je zakázána. Zpráva zůstane ve frontě, dokud nebude odeslána cronem.'));
		}
	}
}
