<?

namespace System\Form\Widget
{
	class Search extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'search';
		const IDENT = 'search';

		protected static $attrs = array(
			"model"    => array("varchar"),
			"conds"    => array("array"),
			"display"  => array("array"),
			"filter"   => array("array"),
			"has"      => array("array"),
		);

		protected static $inputs = array(
			array(
				"ident" => 'search',
				"name"  => '%s_search',
				"type"  => 'text',
				"label" => 'form_input_search',
			),
		);

		protected static $resources = array(
			"scripts" => array('pwf/form/search_tool'),
			"styles"  => array('pwf/form/search_tool'),
		);
	}
}
