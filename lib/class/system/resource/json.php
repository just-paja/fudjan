<?

namespace System\Resource
{
	class Json extends \System\Resource
	{
		public function set_response()
		{
			$cnt = $this->content;
			$cb  = $this->request->get('callback');

			if ($cb) {
				$cnt = $cb.'('.$cnt.');';
			}

			$this->response->set_content($cnt);
		}
	}
}
