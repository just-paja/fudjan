<?

namespace Template
{
	class Tag
	{
		private static $html_attrs = array(
			'*' => array("class", "id", "onclick", "onfocus", "title"),
			'#inputs' => array("onchange", "onkeyup", "name", "value"),
			'#source' => array("src"),
			'#links' => array("href"),
			'meta' => array("name", "content", "http-equiv"),
			'form' => array("method", "action", "enctype"),
			'textarea' => array("required", "rows", "cols", "!value"),
			'input' => array("type", "min", "max", "maxlength", "step", "required", "size", "disabled", "checked", "results", "placeholder"),
			'select' => array("size", "multiple", "required"),
			'button' => array("type"),
			'div' => array(),
			'ul' => array(),
		);

		private static $bool_attrs = array(
			'required',
			'selected',
			'checked'
		);

		private static $html_schema = array(
			'#inputs' => array('select', 'input', 'textarea', 'button'),
			'#source' => array('img', 'iframe', 'script'),
			'#links' => array('a', 'link'),
		);


		public static function __callStatic($name, $args)
		{
			$attrs = &$args[0];
			$o = '<'.$name.self::html_attrs($name, $attrs).'>';

			if (isset($attrs['content'])) {
				$o .= $attrs['content'];
			}

			if (isset($attrs['close']) && $attrs['close']) {
				$o .= '</'.$name.'>';
			}
			
			if (isset($attrs['output']) && $attrs['output'])
				echo $o;

			return $o;
		}


		public static function close($name, $output = false)
		{
			$o = '</'.$name.'>';

			if ($o)
				echo $o;

			return $o;
		}


		public static function get_tag_class($tag)
		{
			foreach (self::$html_schema as $cname=>$objects) {
				if (in_array($tag, $objects)) {
					return self::$html_attrs[$cname];
				}
			}
			return array();
		}


		public static function html_attrs($tag, $attrs)
		{
			$real_attrs = array();

			foreach (self::$bool_attrs as $attr) {
				if (any($attrs[$attr])) {
					$attrs[$attr] = $attr;
				}
			}

			foreach ($attrs as $name=>$attr) {
				if (!is_null($attr) && !is_array($attr) && strlen($attr) && (!isset(self::$html_attrs[$tag]) || self::$html_attrs[$tag] == '*' || in_array($name, self::$html_attrs['*']) || in_array($name, self::$html_attrs[$tag]) || in_array($name, self::get_tag_class($tag)))) {
					$real_attrs[] = $name.'="'.$attr.'"';
				}
			}
			return (strlen($real_attrs) ? ' ':'').implode(' ', $real_attrs);
		}
	}
}
