<?


namespace System\Form\Widget
{
	class Action extends \System\Form\Widget
	{
		const KEEP   = 0;
		const UPLOAD = 1;
		const URL    = 2;
		const NONE   = 3;
		const CREATE = 4;
		const EDIT   = 5;

		protected static $attrs = array(
			"options" => array('list'),
		);

		protected static $input_opts = array(
			self::KEEP   => "form_widget_action_keep",
			self::UPLOAD => "form_widget_action_upload",
			self::URL    => "form_widget_action_use_url",
			self::NONE   => "form_widget_action_none",
			self::CREATE => "form_widget_action_create",
			self::EDIT   => "form_widget_action_edit",
		);

		protected static $default_opts = array(self::KEEP, self::CREATE, self::EDIT, self::NONE);

		protected static $inputs = array(
			array(
				"ident"   => 'action',
				"name"    => '%s_action',
				"type"    => 'radio',
				"is_null" => false,
				"options" => '#{options}',
			),
		);


		protected function init_tools(array $tools = null)
		{
			$par_value = $this->form()->get_input_value_by_name($this->parent->name);
			$value = null;
			$opts  = $this->options;
			$tools = self::$inputs;

			if (empty($opts)) {
				foreach (self::$default_opts as $opt) {
					$opts[$opt] = self::$input_opts[$opt];
				}
			}

			if (is_null($par_value) && isset($opts[self::KEEP])) {
				unset($opts[self::KEEP]);
			}

			if (isset($opts[self::NONE]) && $this->required) {
				unset($opts[self::NONE]);
			}

			if (is_null($par_value) && isset($opts[self::EDIT])) {
				unset($opts[self::EDIT]);
			}

			if (is_null($par_value)) {
				$value = self::CREATE;
			}

			if (count($opts) > 1) {
				$tools[0]['options'] = $opts;
				if (!$this->form()->submited()) {
					$tools[0]['value'] = $value;
				}
				parent::init_tools($tools);
			}
		}


		protected function assemble_value()
		{
			$value = parent::assemble_value();
			return is_null($value) ? self::CREATE:$value;
		}
	}
}

