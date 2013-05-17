<?

namespace System\Form\Widget
{
	class Location extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'location';
		const MODEL = '\System\Gps';
		const IDENT = 'gps';

		protected static $attrs = array();

		protected static $inputs = array(
			array(
				"ident"    => 'action',
				"name"     => '%s_action',
				"type"     => 'action',
				"label"    => 'form_location_input_action',
				"required" => true,
			),
			array(
				"ident"    => 'name',
				"name"     => '%s_name',
				"type"     => 'text',
				"label"    => 'form_location_input_name',
				"required" => true,
			),
			array(
				"ident"    => 'addr',
				"name"     => '%s_addr',
				"type"     => 'text',
				"label"    => 'form_location_input_addr',
				"required" => true,
			),
			array(
				"ident"    => 'gps',
				"name"     => '%s_gps',
				"type"     => 'gps',
				"label"    => 'form_location_input_gps',
				"required" => true,
			),
		);


		protected static $resources = array(
			'scripts' => array('pwf/form/jquery.gmap', 'pwf/form/gps'),
		);
	}
}
