<?

namespace System\Template\Renderer
{
	interface Api
	{
		public function render();
		public function get_suffix();
		public function render_template($path, array $locals = array());
	}
}
