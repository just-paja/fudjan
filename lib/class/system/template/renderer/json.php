<?

namespace System\Template\Renderer
{
	class Json extends \System\Template\Renderer
	{
		public function render_content()
		{
			$slots = $this->get_slots();
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
				$this->response->status(intval(def($data[0]['status'], 200)));
			}

			return json_encode(count($json) == 1 ? $json[0]:$json);
		}


		public function render_template($path, array $locals = array())
		{
			return $this->request->get('callback') ?
				$this->request->get('callback').'('.json_encode($locals).');':
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
