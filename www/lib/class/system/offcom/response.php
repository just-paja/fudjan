<?

namespace System\Offcom
{
	class Response extends \System\Model\Attr
	{
		protected static $attrs = array(
			"content" => array('blob'),
			"headers" => array('text'),
			"status"  => array('int'),
		);


		public function ok()
		{
			return $this->status >= 200 && $this->status <= 300;
		}
	}
}
