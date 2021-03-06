<?php

namespace System\Resource
{
	class Locale extends \System\Resource\Json
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
			if ($this->name == 'list') {
				$this->content = json_encode(array(
					"data" => $this->response->locales->get_available(),
					"status" => 200
				));
			} else {
				$this->content = json_encode(array(
					"data" => $this->response->locales->get_messages($this->name),
					"status" => 200
				));
			}
		}
	}
}
