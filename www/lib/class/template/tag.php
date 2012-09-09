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
			'html' => array('xmlns'),
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
			self::tag($name, (array) $attrs);
		}


		/** Output or return tag with or without content
		 * @param string $name  Tag name
		 * @param array  $attrs Tag attributes
		 * @return string
		 */
		public static function tag($name, array $attrs = array())
		{
			$o = '<'.$name.self::html_attrs($name, (array) $attrs).'>';

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


		/** Output or return closing tag
		 * @param string $name   Tag name
		 * @param bool   $output Output the tag if true
		 * @return string
		 */
		public static function close($name, $output = false)
		{
			$o = '</'.$name.'>';

			if ($o)
				echo $o;

			return $o;
		}


		/** Get attribute class of a tag
		 * @param string $tag Tag name
		 */
		private static function get_tag_class($tag)
		{
			foreach (self::$html_schema as $cname=>$objects) {
				if (in_array($tag, $objects)) {
					return self::$html_attrs[$cname];
				}
			}
			return array();
		}


		/** Print html attributes into string
		 * @param string $tag   Tag name
		 * @param array  $attrs Set of attributes
		 */
		public static function html_attrs($tag, array $attrs = array())
		{
			$real_attrs = array();

			foreach (self::$bool_attrs as $attr) {
				if (any($attrs[$attr])) {
					$attrs[$attr] = $attr;
				}
			}

			foreach ($attrs as $name=>$attr) {
				$is_valid = !is_null($attr) && !is_array($attr) && strlen($attr);
				$available_for_tag = isset(self::$html_attrs[$tag]) && (self::$html_attrs[$tag] == '*' || in_array($name, self::$html_attrs['*']) || in_array($name, self::$html_attrs[$tag]));
				$available_for_tag_class = in_array($name, self::get_tag_class($tag));

				if ($is_valid && ($available_for_tag || $available_for_tag_class)) {
					$real_attrs[] = $name.'="'.$attr.'"';
				}
			}

			return (count($real_attrs) ? ' ':'').implode(' ', $real_attrs);
		}


		/** Output a doctype
		 * @return string
		 */
		public static function doctype()
		{
			$o = '<!DOCTYPE html>';
			echo $o;
			return $o;
		}
	}
}
