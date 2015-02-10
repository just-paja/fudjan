<?

namespace System\Template\Renderer
{
	class Basic extends \System\Template\Renderer
	{
		public function render_template($path, array $locals = array())
		{
			$name = str_replace('/', '-', $path);
			extract($locals);

			ob_start();
			if ($wrap) {
				echo '<div class="template '.$name.'">';
			}

			require $path;

			if ($wrap) {
				echo '</div>';
			}

			$cont = ob_get_contents();
			ob_end_clean();

			return $cont;
		}


		public function get_suffix()
		{
			return 'php';
		}
	}
}
