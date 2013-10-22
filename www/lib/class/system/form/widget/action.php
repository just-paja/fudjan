<?


namespace System\Form\Widget
{
	class Action extends \System\Form\Widget
	{
		const KEEP   = 1;
		const UPLOAD = 2;
		const URL    = 3;
		const NONE   = 4;
		const CREATE = 5;
		const EDIT   = 6;

		const KIND  = 'widget';
		const TYPE  = 'action';
		const IDENT = 'action';

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

		protected static $default_opts = array(self::NONE, self::KEEP, self::CREATE, self::EDIT);

		protected static $inputs = array(
			array(
				"ident"   => 'action_select',
				"name"    => '%s_action',
				"type"    => 'radio',
				"is_null" => false,
				"options" => '#{options}',
			),
		);



		/** Init all widget inputs
		 * @param array $tools List of inputs
		 * @return void
		 */
		protected function init_tools(array $tools = null)
		{
			if ($this->form()->submited()) {
				$value = $this->form()->get_input_value_by_name($this->name.'_action');
			} else {
				$value = null;
			}

			$par_value = $this->form()->get_input_value_by_name($this->parent->name, $value == self::KEEP);
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

			if (is_null($value)) {
				if ($this->form()->submited()) {
					$value = $this->form()->get_input_value_by_name($this->name.'_action');

					if (is_null($value)) {
						if (count($opts) === 1) {
							$value = reset($opts);
						}
					}
				} else {
					if (is_null($par_value)) {
						$keys = array_keys($opts);
						$value = isset($opts[self::NONE]) ? self::NONE:$keys[0];
					} else {
						$value = self::KEEP;
					}
				}
			}

			if (count($opts) > 1) {
				$tools[0]['options'] = $opts;
				if (!$this->form()->submited()) {
					$tools[0]['value'] = $value;
				}

				parent::init_tools($tools);
			}

			$this->form()->ignore_input($this->name.'_action');

			//~ if (!$this->form()->submited()) {
			$this->form()->use_value($this->name, $value);
			//~ }
		}


		protected function assemble_value()
		{
			$value = parent::assemble_value();
			return is_null($value) ? self::CREATE:$value;
		}
	}
}

