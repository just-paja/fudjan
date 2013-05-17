<?

namespace System\Form
{
	class Input extends \System\Form\Element
	{
		protected static $attrs = array(
			"id"           => array("varchar"),
			"name"         => array("varchar"),
			"type"         => array("varchar"),
			"label"        => array("varchar"),
			"kind"         => array("varchar"),
			"content"      => array("text"),
			"info"         => array("varchar"),
			"placeholder"  => array('varchar'),
			"maxlen"       => array('int'),
			"step"         => array('float'),
			"min"          => array('int'),
			"max"          => array('int'),
			"required"     => array('bool'),
			"checked"      => array('bool'),
			"autocomplete" => array('bool'),
			"value"        => array('mixed'),
			"options"      => array('array'),
			"class"        => array('array'),

			// Search tool specific
			"model"    => array("varchar"),
			"conds"    => array("array"),
			"display"  => array("array"),
			"filter"   => array("array"),
			"has"      => array("array"),

			// Image input specific
			"thumb_size" => array("varchar"),
			"disallow_upload" => array("bool"),
			"allow_url"  => array("bool"),

			// Widget specific
			'ident' => array("varchar"),

		);

		protected static $required = array(
			'name', 'kind',
		);

		protected static $kinds = array('input', 'textarea', 'select', 'button', 'search_tool');
		protected static $kinds_content_value = array('textarea');
		protected static $kinds_no_label = array('button');
		protected static $types = array(
			'textarea',
			'select',
			'text',
			'number',
			'date',
			'datetime',
			'file',
			'range',
			'url',
			'email',
			'hidden',
			'button',
			'submit',
			'password',
		);


		const IMAGE_INPUT_SIZE_DEFAULT = '100x100';

		protected static $input_opts = array(
			"image" => array(
				self::ACTION_KEEP   => "form_image_input_keep",
				self::ACTION_NONE   => "form_image_input_none",
				self::ACTION_UPLOAD => "form_image_input_upload",
				self::ACTION_URL    => "form_image_input_url",
			),
			"location" => array(
				self::ACTION_NEW      => "form_location_input_new",
				self::ACTION_EDIT     => "form_location_input_edit",
				self::ACTION_NONE     => "form_location_input_none",
			),
		);

		protected $tools = array();


		protected function construct()
		{
			parent::construct();

			if ($this->type == 'rte') {
				$this->kind = 'textarea';
			}

			!$this->type && self::get_default_type();
			!$this->kind && self::get_default_kind();
			$this->kind = in_array($this->type, self::$kinds) ?
				$this->type:
				(in_array($this->kind, self::$kinds) ? $this->kind:self::get_default_kind());
			!$this->id && $this->id = 'field_'.$this->name;

			$this->type == 'submit' && $this->kind = 'button';

			if (!$this->name) {
				throw new \System\Error\Form('You must enter input name!', $this->type);
			}
		}


		public static function is_allowed_type($type)
		{
			return in_array($type, self::$types);
		}


		public static function is_allowed_kind($kind)
		{
			return in_array($kinds, self::$kinds);
		}


		public static function get_default_type()
		{
			return self::$types[0];
		}


		public static function get_default_kind()
		{
			return self::$kinds[0];
		}


		public function is_value_content()
		{
			return in_array($this->kind, self::$kinds_content_value);
		}


		public function has_label()
		{
			return !in_array($this->kind, self::$kinds_no_label);
		}


		public static function get_input_opts($type)
		{
			if (isset(self::$input_opts[$type])) {
				$opts = array();

				foreach (self::$input_opts[$type] as $label=>$opt) {
					$opts[$label] = l($opt);
				}

				return $opts;
			} else throw new \System\Error\Form("There are no options for input '".$type."'");
		}
	}
}
