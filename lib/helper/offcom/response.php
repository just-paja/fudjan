<?

/** Remote request response
 * @package system
 * @subpackage offcom
 */
namespace Helper\Offcom
{
	/** Remote request response
	 * @package system
	 * @subpackage offcom
	 */
	class Response extends \System\Model\Attr
	{
		/** Attributes */
		protected static $attrs = array(
			"content" => array('blob'),
			"headers" => array('array'),
			"status"  => array('int'),
			"size"    => array('int'),
			"mime"    => array('varchar'),
		);


		public function construct()
		{
			$content = explode("\r\n\r\n", $this->content, 2);
			$headers = explode("\n", $content[0]);

			if (isset($content[1])) {
				$this->content = $content[1];
			} else {
				$this->content = '';
			}

			$h = array();

			foreach ($headers as $row) {
				$row = trim($row);

				if (strpos($row, ':') > 0) {
					$row = explode(":", $row, 2);
					$name = trim($row[0]);
					$value = trim($row[1]);
					$h[$name] = $value;
				}
			}

			$this->headers = $h;

			if (isset($this->headers['Content-Length'])) {
				$this->size = $this->headers['Content-Length'];
			}

			if (isset($this->headers['Content-Type'])) {
				$this->mime = strtolower($this->headers['Content-Type']);
			}

			return $this;
		}


		/** Was there an error while receiving the response?
		 * @return bool
		 */
		public function ok()
		{
			return $this->status >= 200 && $this->status <= 300;
		}
	}
}
