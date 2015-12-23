<?php

namespace System\Resource
{
	class Style extends \System\Resource\Text
	{
		const DIR_SAVE  = '/var/cache/resources/styles';
		const DIR_CACHE = '/var/cache/static';
		const NOT_FOUND = '/* Style not found: %s */';
		const MIME_TYPE = 'text/css';
		const POSTFIX_OUTPUT = '.css';

		static protected $postfixes = array('css', 'less');


		public function read()
		{
			parent::read();

			if (!$this->is_cached()) {
				if (class_exists('\Less_Parser')) {
					$parser = new \Less_Parser();
					$parser->SetOptions(array(
						'compress' => $this->minify
					));

					try {
						$parser->parse($this->content);
					} catch(\Exception $e) {
						throw new \System\Error\Format('Error while parsing LESS styles', $e->getMessage());
					}

					$this->content = $parser->getCss();
				} else throw new \System\Error\MissingDependency('Missing less parser', 'install oyejorge/less.php');
			}
		}
	}
}
