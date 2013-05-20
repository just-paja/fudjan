<?

namespace System\Form
{
	class Container extends \System\Form\Element
	{
		const TYPE_INPUTS    = 'inputs';
		const TYPE_BUTTONS   = 'buttons';
		const TYPE_TAB_GROUP = 'tab_group';
		const TYPE_TAB       = 'tab';

		protected static $attrs = array(
			"id"    => array('varchar'),
			"title" => array('varchar'),
			"name"  => array('varchar'),
			"label" => array('varchar'),
			"type"  => array('varchar'),
			"class" => array('array'),
		);

		protected static $types = array(
			self::TYPE_INPUTS,
			self::TYPE_BUTTONS,
			self::TYPE_TAB,
		);

		protected static $type_models = array(
			self::TYPE_INPUTS    => array('System\\Form\\Input', 'System\\Form\\Widget', 'System\\Form\\Label', 'System\\Form\\Text'),
			self::TYPE_BUTTONS   => array('System\\Form\\Input'),
			self::TYPE_TAB_GROUP => array('System\\Form\\Container'),
			self::TYPE_TAB       => array('System\\Form\\Container'),
		);

		private $elements = array();


		/** Public constructor
		 * @param array $dataray
		 */
		protected function construct()
		{
			if (!$this->type) {
				throw new \System\Error\Form('You must set form container type');
			}

			if (!$this->class) {
				$this->class = array();
			}

			$this->class = array_merge($this->class, array($this->type));

			!$this->name && $this->generate_name();
		}


		/** Add element to the list
		 * @param \System\Form\Element $el
		 * @return void;
		 */
		public function add_element(\System\Form\Element $el)
		{
			$el->form($this->form());
			$fits = false;

			foreach ($this->get_expected_class() as $cname) {
				if ($el instanceof $cname) {
					$fits = true;
					break;
				}
			}

			if ($fits) {
				$this->elements[$el->name] = $el;
			} else throw new \System\Error\Form(sprintf(
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
			$this->name = implode(\System\Form::SEPARATOR_ID, array($this->type, $this->form()->get_count($this->type)));
		}


		public function get_elements()
		{
			return $this->elements;
		}
	}
}
