<?php

namespace System\Resource
{
	class Script extends \System\Resource\Text
	{
		const DIR_SAVE  = '/var/cache/resources/scripts';
		const DIR_CACHE = '/var/cache/static';
		const NOT_FOUND = 'console.log("Jaffascript module not found: %s");';
		const MIME_TYPE = 'text/javascript';
		const POSTFIX_OUTPUT = '.js';

		static protected $postfixes = array('js');


		public function read()
		{
			parent::read();

			if (!$this->is_cached() && $this->minify) {
				try {
					$this->content = \Helper\Minifier\Scripts::minify($this->content);
				} catch (\System\Error\Format $e) {
					$exp = $e->get_explanation();
					$exp[] = $this->get_file_list($this->base);
					$e->set_explanation($exp);

					throw $e;
				}
			}
		}
	}
}
