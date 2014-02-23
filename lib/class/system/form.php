<?

namespace System
{
	class Form extends \System\Model\Attr
	{
		const SEPARATOR_ID = '_';
		const SEPARATOR_INPUT_METHOD = 'input_';
		const TEMPLATE_DEFAULT = 'system/form';
		const LABEL_SUBMIT_DEFAULT = 'send';

		protected static $attrs = array(
			"id"       => array('varchar'),
			"method"   => array('varchar'),
			"action"   => array('varchar'),
			"enctype"  => array('varchar'),
			"heading"  => array('varchar'),
			"desc"     => array('varchar'),
			"bool"     => array('no_prefix'),
			"class"    => array('array'),
			"prefix"   => array('varchar'),
			"use_comm" => array('bool'),
			"renderer" => array('object', "model" => '\System\Template\Renderer'),
			"response" => array('object', "model" => '\System\Http\Response'),
			"request"  => array('object', "model" => '\System\Http\Request'),
		);

		private static $methods_allowed = array('get', 'post', 'put', 'delete');

		protected $data_default  = array();
		protected $data_commited = array();
		protected $data_hidden   = array();
		protected $errors        = array();

		private $counts  = array();
		private $objects = array();
		private $inputs  = array();
		private $ignored = array('submited', 'button_submited');
		private $rendering = array(
			"group"     => false,
			"tab_group" => false,
			"tab"       => false,
		);

		private static $inputs_button = array("button", "submit");


		public static function from_response(\System\Http\Response $response, array $attrs = array())
		{
			$attrs['request'] = $response->request();

			if (empty($attrs['action'])) {
				$attrs['action'] = $response->request()->path;
			}

			$form = new self($attrs);
			$form->response = $response;
			$form->renderer = $response->renderer();
			return $form;
		}


		public static function from_module(\System\Module $module, array $attrs = array())
		{
			return self::from_response($module->response(), $attrs);
		}


		public static function from_renderer(\System\Template\Renderer $ren, array $attrs = array())
		{
			return self::from_response($ren->response(), $attrs);
		}


		public static function from_request(\System\Http\Request $request, array $attrs = array())
		{
			$attrs['request'] = $request;

			if (empty($attrs['action'])) {
				$attrs['action'] = $request->path;
			}

			return new self($attrs);
		}


		/** Constructor addon
		 * @return void
		 */
		protected function construct()
		{
			!$this->method  && $this->method = 'post';
			!$this->id      && $this->id = self::get_generic_id();
			!$this->enctype && $this->enctype = 'multipart/form-data';

			if (is_array($this->default)) {
				$this->data_default = $this->default;
			}

			$this->method = strtolower($this->method);
			$this->take_data_from_request();

			$this->hidden('submited', true);
			$this->data_default['submited'] = false;
		}


		/** Alias to create simple input type
		 * @param string $name Name of called method
		 * @param array  $args Arguments to the function
		 */
		public function __call($name, $args)
		{
			if (strpos($name, self::SEPARATOR_INPUT_METHOD) === 0) {
				$type = substr($name, strlen(self::SEPARATOR_INPUT_METHOD));

				if (!isset($args[0])) {
					throw new \System\Error\Argument(sprintf('You must enter input name as first argument for System\\Form::%s method', $name));
				}

				return $this->input(array(
					"type"     => $type,
					"name"     => $args[0],
					"label"    => def($args[1], ''),
					"required" => def($args[2], false),
					"info"     => def($args[3], ''),
				));

			} else throw new \System\Error\Wtf(sprintf('There is no form method "%s".', $name));
		}


		/** Lookup commited data in input class
		 * @return void
		 */
		protected function take_data_from_request()
		{
			$this->data_commited = $this->request()->input_by_prefix($this->get_prefix(), $this->method);

			if (isset($this->data_commited['data_hidden'])) {
				$this->data_hidden = \System\Json::decode(htmlspecialchars_decode($this->data_commited['data_hidden']));

				$tmp = array();

				if (is_array($this->data_hidden)) {
					foreach ($this->data_hidden as $key=>$val) {
						$tmp[$key] = $val;
					}
				}

				if (is_array($this->data_commited)) {
					foreach ($this->data_commited as $key=>$val) {
						$tmp[$key] = $val;
					}
				}

				$this->data_commited = $tmp;
				unset($this->data_commited['data_hidden']);
			}

			$this->submited = isset($this->data_commited['submited']) ? !!$this->data_commited['submited']:false;
		}


		/** Get value of input by name
		 * @param array $attrs Input attributes
		 * @return mixed
		 */
		public function get_input_value($attrs)
		{
			return $this->get_input_value_by_name($attrs['name']);
		}


		public function set_input_value($name, $value)
		{
			$this->data_default[$name] = $value;
		}


		public function get_input_value_by_name($name, $default = false)
		{
			$value = null;

			if (($default || !$this->submited)) {
				$ref = &$this->data_default;
			}

			if (!$default && $this->submited) {
				$ref = &$this->data_commited;
			}

			return $this->get_input_data_ref($ref, $name);
		}


		public function input_value($name, $default = false)
		{
			return $this->get_input_value_by_name($name, $default);
		}


		/** Get generic ID for this form
		 * @return string
		 */
		protected function get_generic_id()
		{
			return implode(self::SEPARATOR_ID, array('form', substr(md5($this->action), 0, 8)));
		}


		/** Add object to forms' set of objects
		 * @param System\Form\Element $element
		 * @return void
		 */
		protected function &add_object(\System\Form\Element $element)
		{
			$obj = &$this->objects[];
			$obj = $element;
			return $obj;
		}


		/** Start rendering form element container
		 * @param string $type Type of element container (see System\Form\Container)
		 * @param string $name
		 * @param string $label
		 */
		public function group_start($type, $name = '', $label = '')
		{
			$el = new \System\Form\Container(array(
				"name"  => $name ? $name:'',
				"label" => $label,
				"form"  => &$this,
				"type"  => $type,
			));

			if ($this->rendering['tab'] instanceof \System\Form\Container) {
				$this->rendering['group'] = $this->rendering['tab']->add_element($el);
			} else {
				$this->objects[$el->name] = $el;
				$this->rendering['group'] = $this->objects[$el->name];
			}

			return $this->rendering['group'];
		}


		/** Stop rendering form element container
		 * @return void
		 */
		public function group_end()
		{
			$this->rendering['group'] = false;
		}


		/** Check if form container is on, start it otherwise
		 * @param string $type
		 * @return void
		 */
		public function check_rendering_group($type)
		{
			if ($this->rendering['group'] === false || $this->rendering['group']->type != $type) {
				$this->group_start($type, count($this->objects));
			}

			return $this->rendering['group'];
		}


		/** Get generic object name
		 * @return string
		 */
		public function gen_obj_name($type)
		{
			return implode(self::SEPARATOR_ID, array($this->id, $this->inputs_count));
		}


		/** Is the form ready for processing
		 * @return bool
		 */
		public function passed()
		{
			return $this->submited() && $this->is_valid();
		}


		/** Is the form ready for processing
		 * @return bool
		 */
		public function submited()
		{
			return $this->submited;
		}


		public function is_valid()
		{
			$valid = true;

			foreach ($this->objects as $object) {
				$valid = $valid && $object->is_valid();
			}

			return $valid && empty($this->errors);
		}


		/** Get count of element type
		 * @param string $type
		 */
		public function get_count($type)
		{
			if (!isset($this->counts[$type])) {
				$this->counts[$type] = 0;
			}

			return $this->counts[$type];
		}


		/** Add hidden data
		 * @param string $name
		 * @param mixed  $value
		 */
		public function hidden($name, $value)
		{
			$this->data_hidden[$name] = $value;
		}


		/** Check if tab group has started, start it if not
		 * @return $this
		 */
		public function tab_group_check()
		{
			if (!($this->rendering[\System\Form\Container::TYPE_TAB_GROUP] instanceof \System\Form\Container)) {
				$this->tab_group_start();
			}

			return $this;
		}


		/** Start groupping tabs into a group
		 * @return $this
		 */
		public function tab_group_start()
		{
			$el = $this->add_object(new \System\Form\Container(array(
				"type" => \System\Form\Container::TYPE_TAB_GROUP,
				"form" => $this,
			)));

			$this->rendering[$el->type] = $el;
			$this->counts[$el->type]++;
			return $this;
		}


		/** Stop groupping tabs into a group
		 * @return $this
		 */
		public function tab_group_end()
		{
			$this->rendering[\System\Form\Container::TYPE_TAB] = false;
			$this->rendering[\System\Form\Container::TYPE_TAB_GROUP] = false;
			return $this;
		}


		/** Start groupping input containers into tab
		 * @param string $label Tab label
		 * @param string $name  Tab name, usefull for JS calls
		 * @return \System\Form\Container
		 */
		public function tab($label, $name = null)
		{
			$this->group_end();
			$this->tab_end();
			$this->tab_group_check();

			$el = new \System\Form\Container(array(
				"type"  => \System\Form\Container::TYPE_TAB,
				"name"  => $name,
				"label" => $label,
				"form"  => $this,
			));

			$this->counts[$el->type] ++;
			if (($this->rendering[\System\Form\Container::TYPE_TAB_GROUP] instanceof \System\Form\Container) && $this->rendering[\System\Form\Container::TYPE_TAB_GROUP]->type == \System\Form\Container::TYPE_TAB_GROUP) {
				return $this->rendering[$el->type] = $this->rendering[\System\Form\Container::TYPE_TAB_GROUP]->add_element($el);
			} else throw new \System\Error\Form('You must put tab into tab group.');
		}


		/** Stop grouping inputs into current tab
		 * @return $this
		 */
		public function tab_end()
		{
			$this->rendering['tab'] = false;
			return $this;
		}


		/** Add input
		 * @param array $attrs
		 * @param bool  $detached Return input detached from the form
		 * @return System\Form\Input
		 */
		public function input(array $attrs, $detached = false)
		{
			if (in_array($attrs['type'], self::$inputs_button)) {
				$this->check_rendering_group('buttons');
			} else {
				$this->check_rendering_group('inputs');
			}

			$attrs['form'] = &$this;

			if (isset($attrs['value'])) {
				$this->use_value($attrs['name'], $attrs['value'], true);
			}

			if (!isset($attrs['type'])) {
				$attrs['type'] = 'text';
			}

			$attrs['value'] = $this->get_input_value_by_name($attrs['name']);

			if (in_array($attrs['type'], array('checkbox', 'radio')) && empty($attrs['multiple'])) {
				if ($this->submited) {
					$ref = $this->data_commited;
				} else {
					$ref = $this->data_default;
				}

				$ref = $this->get_input_data_ref($ref, $attrs['name']);
				$attrs['checked'] = !is_null($ref) && $ref;
			}

			if (in_array($attrs['type'], array('text', 'number')) && any($attrs['options'])) {
				$attr['type'] = 'select';
			}

			$cname = '\\System\\Form\\Input\\'.ucfirst($attrs['type']);

			if (class_exists($cname)) {
				$el = new $cname($attrs);
			} else {
				$el = new \System\Form\Input($attrs);
			}

			return $detached ? $el:$this->attach($el);
		}


		public function get_rendering_container()
		{
			return $this->rendering['group'];
		}


		public function attach(\System\Form\Element $el)
		{
			$this->inputs[$el->name] = &$this->get_rendering_container()->add_element($el);
			return $this->inputs[$el->name];
		}


		/** Add label
		 * @param string $text
		 * @param input  $for
		 */
		public function label($text, \System\Form\Input &$for = null)
		{
			$this->check_rendering_group('inputs');
			return $this->rendering['group']->add_element(new Form\Label(array(
				"content" => $text,
				"input"   => $for,
				"form"    => $this,
			)));
		}


		public function text($label, $text)
		{
			$this->check_rendering_group('inputs');

			return $this->rendering['group']->add_element(new Form\Text(array(
				"form" => $this,
				"name" => crc32($label),
				"label" => $label,
				"content" => $text)
			));
		}


		/** Add common submit button
		 * @param string $label
		 */
		public function submit($label = self::LABEL_SUBMIT_DEFAULT)
		{
			return $this->input(array(
				"name"    => 'button_submited',
				"value"   => true,
				"type"    => 'submit',
				"label"   => $label,
			));
		}


		/** Render form or add form to processing
		 * @param System\Module $obj    Module to render the form in
		 * @param array         $locals Extra local data
		 * @return mixed
		 */
		public function out(\System\Module $obj = NULL, array $locals = array())
		{
			$this->group_end();
			$this->tab_group_end();

			return $obj instanceof \System\Module ?
				$obj->partial(self::get_default_template(), (array) $locals + array("f" => $this)):
				$this->response->renderer()->partial(self::get_default_template(), array("f" => $this));
		}


		public static function get_default_template()
		{
			return self::TEMPLATE_DEFAULT;
		}


		public function get_hidden_data()
		{
			return $this->data_hidden;
		}


		public function get_prefix()
		{
			!$this->prefix && !$this->no_prefix && $this->setup_prefix();
			return $this->prefix;
		}


		/** Set default form prefix
		 * @return string
		 */
		protected function setup_prefix()
		{
			$this->prefix = $this->id.'_';
		}


		public function get_objects()
		{
			return $this->objects;
		}


		public function report_error($input_name, $msg)
		{
			if (!isset($this->errors[$input_name])) {
				$this->errors[$input_name] = array();
			}

			if (!in_array($msg, $this->errors[$input_name])) {
				$this->errors[$input_name][] = $msg;
			}

			return $this;
		}


		public function get_attr_data()
		{
			return parent::get_data();
		}


		public function get_data($with_prefix=false)
		{
			$out = array();

			foreach ($this->inputs as $input) {
				$out[($with_prefix ? $this->get_prefix():'').$input->name] = $input->val();
			}

			return $out;
		}


		public function get_errors($name = '')
		{
			if ($name) {
				if (isset($this->errors[$name])) {
					$error_list = &$this->errors[$name];
				} else $error_list = array();
			} else {
				$error_list = $this->errors;
			}

			return $error_list;
		}


		public function renderer(\System\Template\Renderer $renderer = null)
		{
			if (!is_null($renderer)) {
				$this->renderer = $renderer;
			}

			return $this->renderer;
		}


		public function response(\System\Http\Response $response = null)
		{
			if (!is_null($response)) {
				$this->response = $response;
			}

			return $this->response;
		}


		public function request(\System\Http\Request $request = null)
		{
			if (!is_null($request)) {
				$this->request = $request;
			}

			return $this->request;
		}


		public function ignore_input($name)
		{
			if (!in_array($name, $this->ignored)) {
				$this->ignored[] = $name;
			}

			return $this;
		}


		public function ignore_inputs(array $names)
		{
			foreach ($names as $name) {
				$this->ignore_input($name);
			}

			return $this;
		}


		public function use_value($name, $val, $default=false)
		{
			if (!$this->submited() || $default) {
				$ref = &$this->get_input_data_ref($this->data_default, $name);
			} else {
				$ref = &$this->get_input_data_ref($this->data_commited, $name);
			}

			$ref = $val;
		}


		private function &get_input_data_ref(&$dataray, $name)
		{
			$name_d = $name;

			do {
				$name = explode('[', $name, 2);
				$name_tmp = str_replace(']', '', array_shift($name));

				if (is_object($dataray)) {
					if ($dataray instanceof \System\Model\Attr) {
						if ($dataray->has_attr($name_tmp)) {

							$d = &$dataray->get_data_ref();
							$dataray = &$d[$name_tmp];

						} else {

							$d = &$dataray->get_opts_ref();
							$dataray = &$d[$name_tmp];

						}
					} else throw new \System\Error\Form(sprintf('Forms can operate only with \System\Model\Attr objects. "%s" was given', get_class($dataray)));
				} else {
					if (!isset($dataray[$name_tmp])) {
						if (count($name) > 0) {
							$dataray[$name_tmp] = array();
						} else {
							$dataray[$name_tmp] = null;
						}
					}

					$dataray = &$dataray[$name_tmp];
				}

				$name = implode('[', $name);
			} while(strlen($name));

			return $dataray;
		}


		public function &get_input($name)
		{
			if (isset($this->inputs[$name])) {
				return $this->inputs[$name];
			} else throw new \System\Error\Argument(sprintf("Input '%s' was not registered inside this form.", $name));
		}


		public function to_object()
		{
			$containers = array();
			$attrs = parent::get_data();
			$attrs['use_comm'] = $this->use_comm;
			$attrs['prefix'] = $this->get_prefix();

			unset($attrs['response']);
			unset($attrs['request']);
			unset($attrs['renderer']);

			foreach ($this->objects as $obj) {
				$containers[] = $obj->to_object();
			}

			$containers[] = array(
				"element" => "input",
				"type"    => 'hidden',
				"name"    => $this->get_prefix() . 'data_hidden',
				"value"   => json_encode($this->data_hidden),
			);

			$attrs['elements'] = $containers;
			$attrs['data'] = $this->get_data(true);
			$attrs['initial_check'] = $this->submited;

			foreach ($attrs['data'] as $key=>$value) {
				if (is_null($value)) {
					unset($attrs['data'][$key]);
				} else if ($value instanceof \DateTime) {
					$attrs['data'][$key] = $value->format('c');
				} else if (method_exists($value, 'to_object')) {
					$attrs['data'][$key] = $value->to_object();
				}

				$input = $this->get_input(str_replace($this->get_prefix(), '', $key));

				if ($input && $input->type == 'password') {
					unset($attrs['data'][$key]);
				}
			}

			return $attrs;
		}


		public function collect_resources(\System\Template\Renderer $ren)
		{
			$ren->content_for('styles', 'bower/pwf-form/styles/form');
			$ren->content_for('styles', 'styles/pwf/form');

			$ren->content_for('scripts', 'bower/pwf-queue/lib/queue');
			$ren->content_for('scripts', 'bower/pwf-comm/lib/comm');
			$ren->content_for('scripts', 'bower/pwf-comm/lib/mods/http');
			$ren->content_for('scripts', 'bower/pwf-comm-form/lib/comm-form');
			$ren->content_for('scripts', 'bower/pwf-locales/lib/locales');

			$ren->content_for('scripts', 'bower/pwf-form/lib/form');
			$ren->content_for('scripts', 'bower/pwf-form/lib/input');

			foreach ($this->inputs as $input) {
				$input->collect_resources($ren);
			}

			return $this;
		}


		public function render(\System\Template\Renderer $ren)
		{
			$this->collect_resources($ren);
			return div(array('pwform'), '<span class="def" style="display:none">'.json_encode($this->to_object()).'</span>');
		}

	}
}
