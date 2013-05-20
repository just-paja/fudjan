<?

namespace System\Form\Widget
{
	class Image extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'image';
		const IDENT = 'image';
		const MODEL = '\System\Image';

		protected static $attrs = array(
			"thumb_size" => array("varchar"),
		);

		protected static $inputs = array(
			array(
				"ident"    => 'action',
				"name"     => '%s_action',
				"type"     => 'action',
				"label"    => 'form_input_image_action',
			),
			array(
				"ident"    => 'file',
				"name"     => '%s_file',
				"type"     => 'file',
				"label"    => 'form_input_image_file',
			),
			array(
				"ident"    => 'url',
				"name"     => '%s_url',
				"type"     => 'url',
				"label"    => 'form_input_image_url',
			),
		);

		protected static $resources = array();
	}
}
