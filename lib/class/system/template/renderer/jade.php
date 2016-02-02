<?php

namespace System\Template\Renderer
{
	class Jade extends \System\Template\Renderer
	{
		const DIR_CACHE = '/var/cache/runtime/jade';

		private $jade;

		public function construct($attrs)
		{
			\System\Directory::check(BASE_DIR.self::DIR_CACHE);
			$cache = \System\Settings::getSafe(array('cache', 'templates'), true);

			if (class_exists('Jade\Jade')) {
				$this->jade = new \Jade\Jade(array(
					'cache' => $cache ? BASE_DIR.self::DIR_CACHE:null
				));
			} else {
				throw new \System\Error\MissingDependency('Could not find jade template compiler.', 'Please install ronan-gloo/jadephp');
			}
		}


		public function render_template($path, array $locals = array())
		{
			$wrap = $locals['wrap'];
			\Jade\Parser::$includeNotFound = false;

			$rendered = $this->jade->render($path, $locals);

			if ($wrap) {
				$rendered = '<div class="template '.$locals['template'].'">'.$rendered.'</div>';
			}

			return $rendered;
		}


		public function get_suffix()
		{
			return 'jade';
		}
	}
}
