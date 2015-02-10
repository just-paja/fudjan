<?

namespace System\Template\Renderer
{
	class Txt extends \System\Template\Renderer
	{
		public function render_template($path, array $locals = array())
		{
			$name = str_replace('/', '-', $path);
			extract($locals);

			ob_start();
			require $path;


			$cont = ob_get_contents();
			ob_end_clean();

			return $cont;
		}


		public function get_suffix()
		{
			return 'txt';
		}
	}
}
