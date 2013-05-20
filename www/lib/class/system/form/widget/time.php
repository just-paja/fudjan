<?

namespace System\Form\Widget
{
	class Time extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'time';
		const IDENT = 'time';
		const MODEL = '\DateTime';

		protected static $attrs = array();

		protected static $inputs = array(
			array(
				"ident" => 'time',
				"name"  => '%s_time',
				"type"  => 'time',
				"label" => 'form_input_time',
				"value" => '#{time}',
				"class" => 'timepicker',
			)
		);

		protected static $resources = array(
			'scripts' => array('pwf/form/time_picker'),
			'styles' => array('pwf/form/timepicker'),
		);


		protected function init_tools(array $tools = null)
		{
			if (!$this->form()->submited()) {
				$this->time = $this->form()->get_input_value_by_name($this->name);
			}

			parent::init_tools();
		}
	}
}
