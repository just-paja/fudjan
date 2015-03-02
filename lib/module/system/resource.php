<?

namespace Module\System
{
	class Resource extends \System\Module
	{
		public function run()
		{
			/** Module that sends resource content
			 * @package modules
			 */

			$this->req('res_src');
			$this->req('res_type');
			$this->req('res_path');

			$resource = \System\Resource::sort_out(array(
				'request'  => $this->request,
				'response' => $this->response,
				'path' => $this->res_path,
				'src'  => $this->res_src,
				'type' => $this->res_type,
			));

			$resource->serve();
		}
	}
}
