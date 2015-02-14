<?

namespace System\Resource
{
	class Locale extends \System\Resource
	{
		public function resolve()
		{
			if ($this->name != 'list') {
				try {
					$this->response->locales->load_messages($this->name);
				} catch (\System\Error\Locales $e) {
					$this->exists = false;
				}
			}

			if ($this->src != 'static') {
				$this->exists = false;
			}
		}


		public function read()
		{
			$this->mime = 'application/json';

			if ($this->name == 'list') {
				$this->content = json_encode($this->response->locales->get_available());
			} else {
				$this->content = json_encode($this->response->locales->get_messages($this->name));
			}
		}
	}
}
