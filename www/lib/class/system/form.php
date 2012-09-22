<?

namespace System
{
	class Form extends \System\Model\Attr
	{
		const SEPARATOR_ID = '_';
		const TEMPLATE_DEFAULT = 'system/form';

		protected static $attrs = array(
			"string" => array('method', 'action', 'enctype', 'heading', 'desc', 'anchor'),
			"bool"   => array('no_prefix'),
			"array"  => array('class'),
		);

		private static $methods_allowed = array('get', 'post', 'put', 'delete');

		protected $data_default  = array();
		protected $data_commited = array();
		protected $data_hidden   = array();

		private $objects = array();
		private $rendering = array(
			"group" => false,
			"tab"   => false,
		);

		private $prefix = '';

		protected $counts = array();


		/** Constructor addon
		 * @return void
		 */
		protected function construct()
		{
			!$this->method && $this->method = 'post';
			!$this->action && $this->action = \System\Page::get_path();
			!$this->id     && $this->id = self::get_generic_id();
			!$this->anchor && $this->anchor = \System\Model\Basic::gen_seoname($this->id, true);

			$this->class = array('yaform');
			$this->take_data_from_input();

			$this->hidden('submited', true);
		}


		/** Lookup commited data in input class
		 * @returns void
		 */
		protected function take_data_from_input()
		{
			$this->data_commited = \System\Input::get_by_prefix($this->get_prefix());
			$this->submited = isset($this->data_commited['submited']) ? !!$this->data_commited['submited']:false;

			if (isset($this->data_commited['data_hidden'])) {
				$this->data_hidden = json_decode(htmlspecialchars_decode($this->data_commited['data_hidden']));
				unset($this->data_commited['data_hidden']);
			}
		}


		/** Get value of input by name
		 * @param array $attrs Input attributes
		 * @returns mixed
		 */
		protected function get_input_value($attrs)
		{
			$value = '';

			if (isset($attrs['value'])) {
				$value = $this->data_default[$attrs['name']] = $attrs['value'];
			}

			if ($this->submited && isset($this->data_commited[$attrs['name']])) {
				$value = $this->data_commited[$attrs['name']];
			}

			return $value;
		}


		/** Get generic ID for this form
		 * @returns string
		 */
		protected function get_generic_id()
		{
			return implode(self::SEPARATOR_ID, array('form', substr(md5($this->action), 0, 8)));
		}


		/** Add object to forms' set of objects
		 * @param System\Form\Element $element
		 * @returns void
		 */
		protected function add_object(\System\Form\Element $element)
		{
			$this->objects[] = $element;
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

			$this->objects[$el->name] = $el;
			$this->rendering['group'] = $this->objects[$el->name];
			$this->counts['inputs'] ++;
			return $this->rendering['group'];
		}


		/** Stop rendering form element container
		 * @returns void
		 */
		public function group_end()
		{
			$this->rendering['group'] = false;
		}


		/** Check if form container is on, start it otherwise
		 * @param string $type
		 * @returns void
		 */
		public function check_rendering_group($type)
		{
			if ($this->rendering['group'] === false) {
				$this->group_start($type);
			}

			return $this->rendering['group'];
		}


		/** Get generic object name
		 * @returns string
		 */
		public function gen_obj_name($type)
		{
			return implode(self::SEPARATOR_ID, array($this->id, $this->inputs_count));
		}


		/** Is the form ready for processing
		 * @returns bool
		 */
		public function passed()
		{
			return def($this->data_commited['submited'], false) === true;
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


		/** Add input
		 * @param array $attrs
		 * @returns System\Form\Input
		 */
		public function input(array $attrs)
		{
			$this->check_rendering_group('inputs');
			$attrs['form '] = &$this;
			$attrs['value'] = $this->get_input_value($attrs);

			return $this->rendering['group']->add_element(new \System\Form\Input($attrs));
		}


		/** Add label
		 * @param string $text
		 * @param input  $for
		 */
		public function label($text, \System\Form\Input &$for = null)
		{
			$this->check_rendering_group('inputs');
			$attrs['form'] = &$this;
			return $this->rendering['group']->add_element(new Form\Label(array("content" => $text, "input" => $for)));
		}


		/** Add common text field
		 * @param string $name
		 * @param string $label [''],
		 * @param bool   $required [false]
		 */
		public function input_text($name, $label = '', $required = false)
		{
			return $this->input(array(
				"name"     => $name,
				"label"    => $label,
				"required" => !!$required,
			));
		}


		/** Add common submit button
		 * @param string $label
		 */
		public function submit($label = self::LABEL_SUBMIT_DEFAULT)
		{
			return $this->input(array(
				"name"    => 'submited',
				"value"   => true,
				"type"    => 'submit',
				"content" => $label,
			));
		}


		/** Render form or add form to processing
		 * @param System\Module $obj    Module to render the form in
		 * @param array         $locals Extra local data
		 * @returns mixed
		 */
		public function out(System\Module $obj = NULL, array $locals = array())
		{
			$this->group_end();
			//~ $this->tab_group_end();

			return $obj instanceof Module ?
				$obj->template(self::get_default_template(), (array) $locals + array("f" => $this)):
				\System\Template::partial(self::get_default_template(), array("f" => $this));
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
		 * @returns string
		 */
		protected function setup_prefix()
		{
			$this->prefix = $this->id.'_';
		}


		public function get_objects()
		{
			return $this->objects;
		}
	}
}
