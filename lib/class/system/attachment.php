<?

namespace System
{
	class Attachment extends \System\Model\Perm
	{
		const TYPE_FILE  = 1;
		const TYPE_IMAGE = 2;

		protected static $attrs = array(
			'name' => array('varchar'),
			'desc' => array('text', 'is_null' => true),
			'type' => array('int', 'is_unsigned' => true, 'options' => array(
				array('value' => self::TYPE_FILE,  'name' => 'attachment_file'),
				array('value' => self::TYPE_IMAGE, 'name' => 'attachment_image'),
			)),

			'file' => array('file', 'is_null' => true),
			'image' => array('image', 'is_null' => true),
		);
	}
}
