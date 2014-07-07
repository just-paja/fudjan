<?

namespace System\Template\Renderer\Driver
{
	class Json extends \System\Template\Renderer\Driver
	{
		public function render()
		{
			$slots = $this->renderer->get_slots();
			$json = array();
			$data = array();

			$this->flush();

			foreach ($slots as $slot=>$partials) {
				while ($partial = array_shift($partials)) {
					def($partial['locals'], array());

					$json[] = $this->render_file(null, $partial['locals']);
					$data[] = $partial['locals'];
				}
			}

			if (count($data) == 1) {
				$this->renderer->response()->status(intval(def($data[0]['status'], 200)));
			}

			return count($json) == 1 ? $json[0]:$json;
		}


		public function render_template($path, array $locals = array())
		{
			return $this->renderer->response()->request()->get('callback') ?
				$this->renderer->response()->request()->get('callback').'('.json_encode($locals).');':
				json_encode($locals);
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
