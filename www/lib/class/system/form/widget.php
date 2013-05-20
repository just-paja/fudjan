<?

namespace System\Form
{
	abstract class Widget extends \System\Form\Element
	{
		const MODEL = null;
		const KIND  = 'input';
		const TYPE  = 'widget';
		const IDENT = 'widget';

		protected static $expected;
		protected static $inputs;
		protected static $resources = array();
		protected static $ignore_default_attrs = array();
		protected static $default_attrs = array(
			"id"       => array("varchar"),
			"name"     => array("varchar"),
			"type"     => array("varchar"),
			"label"    => array("varchar"),
			"kind"     => array("varchar"),
			"info"     => array("varchar"),
			"required" => array('bool'),
			"parent"   => array('object', "model" => '\System\Form\Widget'),
			"class"    => array('array'),
		);

		protected $tools  = array();
		protected $value  = null;


		public function __construct(array $dataray = array())
		{
			$model = get_class($this);
			if (!in_array($model, self::$ignore_default_attrs)) {
				foreach (self::$default_attrs as $attr=>$def) {
					$model::$attrs[$attr] = $def;
				}

				self::$ignore_default_attrs[] = $model;
			}

			parent::__construct($dataray);
		}


		protected function construct()
		{
			$this->init_tools();
		}


		/** Init helper inputs
		 * @return void
		 */
		protected function init_tools(array $tools = null)
		{
			$model = get_class($this);
			$tools = is_null($tools) ? $model::$inputs:$tools;
			$value = $this->form()->get_input_value_by_name($this->name);

			foreach ($tools as $attrs) {
				foreach ($attrs as $attr_name=>&$attr_val) {
					$matches = array();

					if (is_string($attr_val) && preg_match('/\#\{([a-z\_]+)\}/', $attr_val, $matches)) {
						$name = $matches[1];
						$attr_val = $this->$name;
					}
				}

				$attrs['name'] = sprintf($attrs['name'], $this->name);

				if (any($attrs['label'])) {
					$attrs['label'] = l($attrs['label']);
				}

				$attrs['form']   = $this->form();
				$attrs['parent'] = $this;

				if (!$this->form()->submited() && isset($attrs['value'])) {
					$this->form()->use_value($attrs['name'], $attrs['value']);
				}

				$value = $this->form()->get_input_value_by_name($this->name);

				$this->tools[$attrs['ident']] = $this->form()->input($attrs, true);
				$obj = $this->form()->ignore_input($attrs['name']);
			}

			$value = $this->assemble_value();

			if (!is_null($this::MODEL) && $value) {
				$object_model = $this::MODEL;

				if (!($value instanceof $object_model)) {
					if (method_exists($object_model, 'from_form')) {
						$value = $object_model::from_form($value);
					} else {
						$value = new $object_model($value);
					}
				}
			}

			$this->form()->use_value($this->name, $value);
		}


		/** Assemble value from inputs
		 * @return array|null Returns null if all values are empty
		 */
		protected function assemble_value()
		{
			if ($this->form()->submited()) {
				$model = get_class($this);
				$value = array();
				$empty = true;

				if (count($this->tools) > 1) {
					foreach ($this->tools as $tool) {
						$v = $this->form()->get_input_value_by_name($tool->name);
						$value[$tool->ident] = $v;
						$empty = $empty && !$v;
					}

					if (!$empty && isset($value['action'])) {
						if ($value['action'] == \System\Form\Widget\Action::NONE) {
							$value = null;
						}
					}
				} else {
					$keys  = array_keys($this->tools);
					$value = $this->form()->get_input_value_by_name($this->tools[$keys[0]]->name);
					$empty = false;
				}

				return $empty ? null:$value;
			} else return $this->form()->get_input_value_by_name($this->name);
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


		/** Get tool count
		 * @return int
		 */
		public function get_tool_count()
		{
			return count($this->tools);
		}
	}
}
