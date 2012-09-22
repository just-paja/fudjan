<?

namespace System\Form
{
	class Container extends \System\Form\Element
	{
		const TYPE_INPUTS       = 'inputs';
		const TYPE_BUTTONS      = 'buttons';
		const TYPE_TABS         = 'tabs';

		protected static $attrs = array(
			"string" => array('id', 'class', 'title', 'name', 'label', 'type'),
		);

		protected static $types = array(
			self::TYPE_INPUTS,
			self::TYPE_BUTTONS,
			self::TYPE_TABS,
		);

		protected static $type_models = array(
			self::TYPE_INPUTS  => array('System\\Form\\Input', 'System\\Form\\Label'),
			self::TYPE_BUTTONS => array('System\\Form\\Input'),
			self::TYPE_TABS    => array(),
		);

		private $elements = array();


		/** Public constructor
		 * @param array $dataray
		 */
		protected function construct(array $dataray)
		{
			$this->use_form($this->opts['form']);

			if (!$this->type) {
				throw new \MissingArgumentException('You must set form container type');
			}

			!$this->name && $this->generate_name();
			!$this->id   && $this->id = $this->name;
		}


		/** Add element to the list
		 * @param \System\Form\Element $el
		 * @return void;
		 */
		public function add_element(\System\Form\Element $el)
		{
			$el->form = $this->form;
			if (in_array(get_class($el), $this->get_expected_class())) {
				$this->elements[$el->name] = $el;
			} else throw new \InvalidArgumentException(sprintf(
				'Form container %s cannot accomodate element of type %s',
				$this->type,
				get_class($el)
			));
			
			return $el;
		}


		/** Get acceptable classnames for this container type
		 * @return string[]
		 */
		private function get_expected_class()
		{
			return self::$type_models[$this->type];
		}


		/** Generate and use some object name
		 * @return void
		 */
		private function generate_name()
		{
			$this->name = implode(\System\Form::SEPARATOR_ID, array('container', $this->form->get_count($this->type)));
		}
		
		
		public function get_elements()
		{
			return $this->elements;
		}
	}
}
