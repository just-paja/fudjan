<?

namespace System
{
	class Attachment extends \System\Model\Perm
	{
		const TYPE_FILE  = 1;
		const TYPE_IMAGE = 2;

		protected static $attrs = array(
			'name' => array("type" => 'varchar'),
			'desc' => array("type" => 'text', 'is_null' => true),
			'type' => array("type" => 'int', 'is_unsigned' => true, 'options' => array(
				array('value' => self::TYPE_FILE,  'name' => 'attachment_file'),
				array('value' => self::TYPE_IMAGE, 'name' => 'attachment_image'),
			)),

			'file' => array("type" => 'file', 'is_null' => true),
			'image' => array("type" => 'image', 'is_null' => true),
		);
	}
}
