<?

namespace System\Form\Widget
{
	class DateTime extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'datetime';
		const IDENT = 'datetime';
		const MODEL = '\DateTime';

		protected static $attrs = array(
			"date" => array("object", "model" => '\DateTime'),
			"time" => array("object", "model" => '\DateTime'),
		);

		protected static $inputs = array(
			array(
				"ident" => 'date',
				"name"  => '%s_date',
				"type"  => 'date',
				"label" => 'form_input_datetime_date',
				"value" => '#{date}',
			),
			array(
				"ident" => 'time',
				"name"  => '%s_time',
				"type"  => 'time',
				"label" => 'form_input_datetime_time',
				"value" => '#{time}',
			),
		);

		protected static $resources = array();


		protected function init_tools(array $tools = null)
		{
			if (!$this->form()->submited()) {
				$value = $this->form()->get_input_value_by_name($this->name);
				$this->date = $value;
				$this->time = $value;
			}

			parent::init_tools();
		}


		protected function assemble_value()
		{
			$value = parent::assemble_value();

			if (is_array($value)) {
				$val = array();

				if (isset($value['date'])) $val[] = $this->form()->response->locales()->format_date($value['date'], 'sql-date', \System\Locales::TRANS_NONE);
				if (isset($value['time'])) $val[] = $this->form()->response->locales()->format_date($value['time'], 'sql-time', \System\Locales::TRANS_NONE);

				if (any($value)) {
					$value = new \DateTime(implode(' ', $val));
				}
			}

			return $value;
		}
	}
}
