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

			if (class_exists('Jade\Jade')) {
				$this->jade = new \Jade\Jade(array(
					'cache' => BASE_DIR.self::DIR_CACHE
				));
			} else {
				throw new \System\Error\MissingDependency('Could not find jade template compiler.', 'Please install ronan-gloo/jadephp');
			}
		}


		public function render_template($path, array $locals = array())
		{
			$wrap = $locals['wrap'];

			ob_start();

			if ($wrap) {
				echo '<div class="template '.$locals['template'].'">';
			}

			$file = $this->jade->cache($path);
			extract($locals);

			try {
				include $file;
			} catch (\Exception $e) {
				if (!($e instanceof \System\Error)) {
					throw new \System\Error\Code('Failed to render jade template.', $e->getMessage(), $path);
				} else throw $e;
			}

			if ($wrap) {
				echo '</div>';
			}

			$out = ob_get_contents();
			ob_end_clean();

			return $out;
		}


		public function get_suffix()
		{
			return 'jade';
		}
	}
}
