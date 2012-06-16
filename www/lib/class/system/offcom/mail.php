<?

namespace Core\Offcom
{
	class Mail extends \Core\System\Model\Offcom
	{
		private static $errors = array();
		
		public static function create(array $rcpt, array $headers, $msg)
		{
			$new_headers = array(
				"X-Mailer"     => \Core\System\Output::introduce(),
				"Content-Type" => 'text/plain; charset=utf-8',
			);

			foreach ($headers as $header=>$value) {
				$new_headers[strtolower($header)] = trim($value);
			}

			$instance = new self($rcpt, $new_headers, $msg);
			return $instance;
		}


		private function __construct(array $rcpt, array $headers, $msg)
		{
			$this->rcpt    = $rcpt;
			$this->headers = $headers;
			$this->msg     = $msg;
			$this->status  = self::STATUS_NEW;
		}


		private function validate()
		{
			foreach ($this->rcpt as $member) {
				if (!self::isAddrValid($member)) {
					throw new \Core\System\Model\OffcomException("E-mail address '".$member."' is not valid!");
				}
			}
			return true;
		}


		public function send()
		{
			try {
				$this->validate();
			} catch (\Core\System\Model\OffcomException $e) {
				$this->status = self::STATUS_FAILED;
				message('error', l("Cannot send e-mail"), $e->msg());
				return false;
			}

			$objhead = $this->headers;
			$body    = array();
			$headers = array();
			$rcpt    = implode(', ', $this->rcpt);

			$objhead['subject'] = '=?UTF-8?B?'.base64_encode($objhead['subject']).'?=';

			foreach ($objhead as $header=>$value) {
				$headers[] = ucfirst($header).": ".$value;
			}

			$body[] = implode("\n", $headers)."\n";
			$body[] = strip_tags($this->msg);
			$this->status = self::STATUS_SENDING;

			if (mail($rcpt, $objhead['subject'], '', implode("\n", $body))) {
				$this->status = self::STATUS_SENT;
			} else $this->status = self::STATUS_FAILED;
			
			return $this->status;
		}


		private static function isAddrValid($data, $strict = false)
		{
			$regex = $strict ? '/^([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i' : '/^([*+!.&#$|\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i';
			return preg_match($regex, trim($data), $matches);
		}

	}
}
