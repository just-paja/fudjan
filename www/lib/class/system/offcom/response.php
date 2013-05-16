<?

/** Remote request response
 * @package system
 * @subpackage offcom
 */
namespace System\Offcom
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
			"headers" => array('text'),
			"status"  => array('int'),
		);


		/** Was there an error while receiving the response?
		 * @return bool
		 */
		public function ok()
		{
			return $this->status >= 200 && $this->status <= 300;
		}
	}
}
