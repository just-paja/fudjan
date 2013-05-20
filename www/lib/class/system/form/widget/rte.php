<?

namespace System\Form\Widget
{
	class Rte extends \System\Form\Widget
	{
		const KIND  = 'textarea';
		const TYPE  = 'rte';
		const IDENT = 'date';

		protected static $attrs = array();

		protected static $inputs = array(
			array(
				"ident"    => 'rte',
				"name"     => '%s_rte',
				"type"     => 'textarea',
				"label"    => 'form_input_text',
			)
		);

		protected static $resources = array(
			"scripts" => array('pwf/lib/rte'),
			"styles"  => array('pwf/form/rte'),
		);
	}
}
