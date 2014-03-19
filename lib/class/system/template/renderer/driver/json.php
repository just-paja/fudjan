<?

namespace System\Template\Renderer\Driver
{
	class Json extends \System\Template\Renderer\Driver
	{
		public function render()
		{
			$slots = $this->renderer->get_slots();
			$json = array();

			$this->flush();

			foreach ($slots as $slot=>$partials) {
				while ($partial = array_shift($partials)) {
					$json[] = $this->render_file(null, def($partial['locals'], array()));
				}
			}

			return count($json) == 1 ? $json[0]:$json;
		}


		public function render_template($path, array $locals = array())
		{
			return json_encode($locals);
		}


		public function render_file($name, array $locals = array())
		{
			return $this->render_template($name, $locals);
		}


		public function get_suffix()
		{
			return 'json';
		}
	}
}
