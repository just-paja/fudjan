<?

namespace System\Form
{
	class Widget extends \System\Form\Element
	{
		const MODEL = null;
		const KIND  = 'input';
		const TYPE  = 'widget';
		const IDENT = 'widget';

		protected static $expected;
		protected static $inputs;

		protected $tools = array();
		protected $value = null;


		protected function construct()
		{
			if (empty($this::$attrs)) {
				$this::$attrs = array(
					"id"           => array("varchar"),
					"name"         => array("varchar"),
					"type"         => array("varchar"),
					"label"        => array("varchar"),
					"kind"         => array("varchar"),
					"info"         => array("varchar"),
					"required"     => array('bool'),
				);
			}

			parent::construct();
			$this->init_tools();
		}


		/** Init helper inputs
		 * @return void
		 */
		protected function init_tools()
		{
			$model = get_class($this);
			$value = $this->form()->get_input_value_by_name($this->name);

			foreach ($model::$inputs as $attrs) {
				$attrs['name']  = sprintf($attrs['name'], $this->name);
				$attrs['label'] = l($attrs['label']);
				$attrs['form']  = $this->form();
				$value = $this->form()->get_input_value_by_name($this->name);

				$this->tools[$attrs['ident']] = $this->form()->input($attrs, true);
				$this->form()->ignore_input($attrs['name']);
			}

			if ($this->form()->submited()) {
				$value = $this->assemble_value();

				if (!is_null($this::MODEL) && $value) {
					$object_model = $this::MODEL;
					$value = new $object_model($value);
				}

				$this->form()->use_value($this->name, $value);
			}
		}


		/** Assemble value from inputs
		 * @return array|null Returns null if all values are empty
		 */
		protected function assemble_value()
		{
			$model = get_class($this);
			$value = array();
			$empty = true;

			foreach ($this->tools as $tool) {
				$v = $this->form()->get_input_value_by_name($tool->name);
				$value[$tool->ident] = $v;
				$empty = $empty && !$v;
			}

			return $empty ? null:$value;
		}


		/** Tool getter
		 * @return array List of objects
		 */
		public function get_tools()
		{
			return $this->tools;
		}


		/** Pass resources to renderer
		 * @return $this
		 */
		public function use_resources(\System\Template\Renderer $ren)
		{
			foreach ($this::$resources as $name=>$content) {
				if (is_array($content)) {
					foreach ($content as $row) {
						$ren->content_for($name, $row);
					}
				} else {
					$ren->content_for($name, $content);
				}
			}

			return $this;
		}


		/** Widget - function that can be publicly overriden. Override this if you have a very special widget
		 * @return string
		 */
		public function render(\System\Template\Renderer $ren)
		{
			return \System\Form\Renderer::render_widget($ren, $this);
		}
	}
}
