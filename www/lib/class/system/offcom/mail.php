<?

/** Email handling
 * @package system
 * @subpackage offcom
 */
namespace System\Offcom
{
	/** E-mail handling class
	 * @package system
	 * @subpackage offcom
	 * @uses System\Model\Attr
	 */
	class Mail extends \System\Model\Attr
	{
		const STATUS_SENT    = 1;
		const STATUS_READY   = 2;
		const STATUS_SENDING = 3;
		const STATUS_FAILED  = 4;


		/** Attributes */
		protected static $attrs = array(
			"subject"  => array('varchar', "required" => true),
			"message"  => array('text', "required" => true),
			"rcpt"     => array('array', "required" => true),
			"headers"  => array('array'),
			"from"     => array('string', "is_null" => false),
			"reply_to" => array('string', "is_null" => false),
			"status"   => array('int', "is_unsigned" => true),
		);


		/** Headers that must be sent */
		protected static $default_headers = array(
			"Content-Type" => 'text/plain; charset=utf-8',
		);


		/** Create email object
		 * @param string $subject Subject encoded in UTF-8
		 * @param string $message Text message encoded in UTF-8
		 * @param array  $rcpt    Recipients in nice format
		 * @param string $from    E-mail address of sender in nice format
		 * @return new self
		 */
		public static function create($subject, $message, array $rcpt, $from = null)
		{
			foreach ($rcpt as &$r) {
				$r = trim($r);
			}

			return new self(array(
				"subject" => $subject,
				"message" => $message,
				"rcpt"    => $rcpt,
				"from"    => $from,
				"status"  => self::STATUS_READY,
			));
		}


		/** Create and immediately send a message
		 * @param string $subject Subject encoded in UTF-8
		 * @param string $message Text message encoded in UTF-8
		 * @param array  $rcpt    Recipients in nice format
		 * @param string $from    E-mail address of sender in nice format
		 * @return bool
		 */
		public static function post($subject, $message, array $rcpt, $from = null)
		{
			$msg = self::create($subject, $message, $rcpt, $from);
			return $msg->send();
		}


		/** Get set of default e-mail headers
		 * @return array
		 */
		public static function get_default_headers()
		{
			if (!isset(self::$default_headers['X-Mailer'])) {
				self::$default_headers["X-Mailer"] = introduce();
			}

			return self::$default_headers;
		}


		/** Get sender or default sender if not set
		 * @return string
		 */
		public function get_sender()
		{
			return is_null($this->from) ? cfg('offcom', 'default', 'email_sender'):$this->from;
		}


		/** Validate e-mail before sending
		 * @return bool
		 */
		private function validate()
		{
			foreach ($this->rcpt as $member) {
				if (!self::isAddrValid($member)) {
					throw new \System\Error\Format(sprintf('Recipient ".$member." is not formatted according to RFC 2822.', $member));
				}
			}

			if (self::isAddrValid($this->get_sender())) {
				throw new \System\Error\Format(sprintf('Sender "%s" is not formatted according to RFC 2822.', $this->get_sender()));
			}

			return true;
		}


		/** Get subject encoded with base64 and UTF-8
		 * @return string
		 */
		private function get_encoded_subject()
		{
			return '=?UTF-8?B?'.base64_encode($this->subject).'?=';
		}


		/** Send email message object
		 * @return int
		 */
		public function send()
		{
			$this->validate();

			$body = array();
			$headers_str = array();

			$rcpt    = implode(', ', $this->rcpt);
			$headers = $this->get_default_headers();
			$headers['From'] = $this->get_sender();
			$headers['Subject'] = $this->get_encoded_subject();

			if ($this->reply_to) {
				if (self::isAddrValid($this->reply_to)) {
					$headers['Reply-To'] = $this->reply_to;
				} else throw new \System\Error\Format(sprintf('Reply-To "%s" is not formatted according to RFC 2822.', $this->get_sender()));
			}

			foreach ($headers as $header=>$value) {
				$headers_str[] = ucfirsts($header).": ".$value;
			}

			$body[] = implode("\n", $headers_str)."\n";
			$body[] = strip_tags($this->message);
			$body = implode("\n", $body);
			$this->status = self::STATUS_SENDING;

			if (mail($rcpt, $this->get_encoded_subject(), '', $body)) {
				$this->status = self::STATUS_SENT;
			} else $this->status = self::STATUS_FAILED;

			return $this->status;
		}


		/** Validate email address against RFC 2822
		 * @param string $email
		 * @param bool   $strict
		 * @return bool
		 */
		private static function isAddrValid($email, $strict = false)
		{
			$regex = $strict ? '/^([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i' : '/^([*+!.&#$|\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})$/i';
			return preg_match($regex, trim($email), $matches);
		}

	}
}
