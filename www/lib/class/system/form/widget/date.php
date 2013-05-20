<?

namespace System\Form\Widget
{
	class Date extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'date';
		const IDENT = 'date';
		const MODEL = '\DateTime';

		protected static $attrs = array();

		protected static $inputs = array(
			array(
				"ident" => 'date',
				"name"  => '%s_date',
				"type"  => 'date',
				"label" => 'form_input_date',
				"value" => '#{date}',
				"class" => 'datepicker',
			)
		);

		protected static $resources = array(
			'scripts' => array('pwf/form/date_picker'),
			'styles' => array('pwf/form/datepicker'),
		);


		protected function init_tools(array $tools = null)
		{
			if (!$this->form()->submited()) {
				$this->date = $this->form()->get_input_value_by_name($this->name);
			}

			parent::init_tools();
		}
	}
}
