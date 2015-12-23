<?php

namespace System\Resource
{
	class Json extends \System\Resource
	{
		public function set_headers()
		{
			$this->mime = 'application/json';
			return parent::set_headers();
		}


		public function set_response()
		{
			$cnt = $this->content;
			$cb  = $this->request->get('callback');

			if ($cb) {
				$cnt = $cb.'('.$cnt.');';
				$this->response->header('Content-Length', strlen($cnt));
			}

			$this->response->set_content($cnt);
		}
	}
}
