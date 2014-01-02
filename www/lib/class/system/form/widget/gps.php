<?

namespace System\Form\Widget
{
	class Gps extends \System\Form\Widget
	{
		protected static $attrs = array(
			"id"           => array("varchar"),
			"name"         => array("varchar"),
			"type"         => array("varchar"),
			"label"        => array("varchar"),
			"kind"         => array("varchar"),
			"info"         => array("varchar"),
			"required"     => array('bool'),
		);

		const KIND  = 'input';
		const TYPE  = 'location';
		const MODEL = '\System\Gps';
		const IDENT = 'gps';

		protected static $inputs = array(
			array(
				"ident"    => 'lat',
				"name"     => '%s_lat',
				"type"     => 'text',
				"label"    => 'form_gps_input_lat',
				"required" => true,
			),
			array(
				"ident"    => 'lng',
				"name"     => '%s_lng',
				"type"     => 'text',
				"label"    => 'form_gps_input_lng',
				"required" => true,
			),
		);


		protected static $resources = array(
			'scripts' => array('scripts/pwf/form/jquery.gmap', 'scripts/pwf/form/gps'),
		);
	}
}
