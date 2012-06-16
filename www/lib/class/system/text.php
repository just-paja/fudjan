<?

namespace System
{
	class Text extends System\BasicModel
	{
		static $table = 'text';
		static $id_col = 'id_text';
		static $attrs = array(
			"string" => array('name', 'lang', 'text'),
			"datetime" => array('created_at', 'updated_at'),
			"bool" => array('public'),
		);
	}
}
