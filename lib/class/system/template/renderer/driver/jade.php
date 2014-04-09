<?

namespace System\Template\Renderer\Driver
{
	class Jade extends \System\Template\Renderer\Driver
	{
		const DIR_CACHE = '/var/cache/jade';

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
			$name = str_replace('/', '-', $path);
			$wrap = $locals['wrap'];
			$out = '';

			if ($wrap) {
				$out = '<div class="template '.$locals['template'].'">';
			}

			try {
				$out .= $this->jade->render($path, $locals);
			} catch (\Exception $e) {
				if (!($e instanceof \System\Error)) {
					throw new \System\Error\Code('Failed to render jade template.', $e->getMessage(), $path);
				} else throw $e;
			}

			if ($wrap) {
				$out .= '</div>';
			}

			return $out;
		}


		public function get_suffix()
		{
			return 'jade';
		}
	}
}
