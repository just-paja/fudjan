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
					'prettyprint' => true,
					'cache'       => BASE_DIR.self::DIR_CACHE
				));
			} else {
				throw new \System\Error\Wtf('Could not find jade template compiler.');
			}
		}


		public function render_template($path, array $locals = array())
		{
			ob_start();
			try {
				$this->jade->render($path, $locals);
			} catch (\Exception $e) {
				throw new \System\Error\Code($e->getMessage(), $path);
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
