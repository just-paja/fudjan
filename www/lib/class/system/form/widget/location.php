<?

namespace System\Form\Widget
{
	class Location extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'location';
		const MODEL = '\System\Location';
		const IDENT = 'location';

		protected static $attrs = array();

		protected static $inputs = array(
			array(
				"ident"    => 'action',
				"name"     => '%s_action',
				"type"     => 'action',
				"label"    => 'form_location_input_action',
			),
			array(
				"ident"    => 'name',
				"name"     => '%s_name',
				"type"     => 'text',
				"label"    => 'form_location_input_name',
			),
			array(
				"ident"    => 'addr',
				"name"     => '%s_addr',
				"type"     => 'text',
				"label"    => 'form_location_input_addr',
			),
			array(
				"ident"    => 'gps',
				"name"     => '%s_gps',
				"type"     => 'gps',
				"label"    => 'form_location_input_gps',
			),
		);


		protected static $resources = array(
			'scripts' => array('pwf/form/autocompleter', 'pwf/form/location_picker'),
			'styles'  => array('pwf/form/autocompleter'),
		);
	}
}
