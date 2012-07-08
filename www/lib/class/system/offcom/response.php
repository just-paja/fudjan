<?

namespace System\Offcom
{
	class Response extends \System\Model\Attr
	{
		protected static $attrs = array(
			"string" => array('content', 'headers'),
			"int" => array('status'),
		);


		public function ok()
		{
			return $this->status >= 200 && $this->status <= 300;
		}
	}
}
