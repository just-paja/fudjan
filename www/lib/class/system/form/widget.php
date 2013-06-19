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
			$widget_tools = array();

			foreach ($tools as $key=>$attrs) {
				if (!isset($attrs['ident'])) {
					throw new \System\Error\Model(sprintf("You must define attribute ident for widget tool '%s' of widget '%s'.", $key, get_class($this)));
				}

				// Model validation
				if ($attrs['ident'] == 'action') {
					if (empty($attrs['type'])) {
						$attrs['type'] = 'action';
					} elseif ($attrs['type'] != 'action') {
						throw new \System\Error\Model(sprintf("Widget tool ident 'action' is reserved for action widget in widget '%s'!", get_class($this)));
					}
				}

				foreach ($attrs as $attr_name=>&$attr_val) {
					$matches = array();

					if (is_string($attr_val) && preg_match('/\#\{([a-z\_]+)\}/', $attr_val, $matches)) {
						$name = $matches[1];
						$attr_val = $this->$name;
					}
				}

				$attrs['name'] = sprintf($attrs['name'], $this->name);

				if (any($attrs['label'])) {
					$attrs['label'] = $attrs['label'];
				}

				$attrs['form']   = $this->form();
				$attrs['parent'] = $this;

				if (empty($attrs['value'])) {
					if (count($tools) === 1) {
						$attrs['value'] = $value;
					} else {
						if (is_array($value) && isset($value[$attrs['ident']])) {
							$attrs['value'] = $value[$attrs['ident']];
						}

						if ($value instanceof \System\Model\Attr) {
							$ident = $attrs['ident'];
							$val = $value->$ident;

							if (any($val)) {
								$attrs['value'] = $val;
							}
						}
					}
				}

				if (!$this->form()->submited() && isset($attrs['value'])) {
					$this->form()->use_value($attrs['name'], $attrs['value']);
				}

				// Mark tool required or not according to widget status
				if ($this->required && isset($attrs['required']) && $attrs['required']) {
					if (isset($widget_tools['action'])) {
						// Widgets with action tool
						$attrs['required'] = $this->form()->input_value($widget_tools['action']->name) != \System\Form\Widget\Action::KEEP;
					} else {
						// Widgets without action tool
						$attrs['required'] = $this->required;
					}
				} else {
					$attrs['required'] = false;
				}

				$value = $this->form()->get_input_value_by_name($this->name);

				$widget_tools[$attrs['ident']] = $this->form()->input($attrs, true);
				$obj = $this->form()->ignore_input($attrs['name']);
			}

			$this->tools = $widget_tools;
			$this->guess_value();
		}


		protected function guess_value()
		{
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

						if (is_array($v)) {
							if (any($v['error'])) {
								$v = null;
								$this->form()->use_value($tool->name, $v);
							}
						}

						$value[$tool->ident] = $v;

						if ($tool->required && empty($v)) {
							break;
						} else {
							$empty = false;
						}
					}

					if (!$empty && isset($value['action'])) {
						if ($value['action'] == \System\Form\Widget\Action::NONE) {
							$value = null;
						}

						if ($value['action'] == \System\Form\Widget\Action::KEEP) {
							$value = $this->form()->get_input_value_by_name($this->name, true);
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


		public function is_valid()
		{
			$valid = true;

			if ($this->form()->submited()) {
				if ($this->required && is_null($this->form()->input_value($this->name))) {
					v($this->form()->input_value($this->name));
					$this->form()->report_error($this->name, 'form_input_empty');
					$valid = false;
				}
			}

			return $valid;
		}
	}
}
