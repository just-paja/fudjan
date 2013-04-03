<?

namespace System\Template
{
	abstract class Tag
	{
		private static $html_attrs = array(
			'*' => array('class', 'id', 'onclick', 'onfocus', 'title'),
			'#inputs'   => array('onchange', 'onkeyup', 'name', 'value'),
			'#source'   => array('src'),
			'#links'    => array('href'),
			'#sizeable' => array('width', 'height'),
			'iframe'    => array('frameborder', 'allowfullscreen'),
			'meta'      => array('name', 'content', 'http-equiv'),
			'form'      => array('method', 'action', 'enctype'),
			'textarea'  => array('required', 'rows', 'cols', '!value'),
			'input'     => array('type', 'min', 'max', 'maxlength', 'step', 'required', 'size', 'disabled', 'checked', 'results', 'placeholder', 'autocomplete'),
			'select'    => array('size', 'multiple', 'required'),
			'option'    => array('selected', 'value'),
			'button'    => array('type'),
			'html'      => array('xmlns'),
			'label'     => array('for'),
		);

		private static $noclose_tags = array(
			'input',
			'img',
			'meta'
		);

		private static $bool_attrs = array(
			'required',
			'selected',
			'checked'
		);

		private static $attr_separators = array(
			'content' => '',
			'style'   => ';',
			'class'   => ' ',
		);

		private static $html_schema = array(
			'#inputs'   => array('select', 'input', 'textarea', 'button', 'option'),
			'#source'   => array('img', 'iframe', 'script'),
			'#links'    => array('a', 'link'),
			'#sizeable' => array('iframe', 'img', 'video'),
		);


		public static function __callStatic($name, $args)
		{
			$attrs = &$args[0];
			return self::tag($name, (array) $attrs);
		}


		/** Output or return tag with or without content
		 * @param string $name  Tag name
		 * @param array  $attrs Tag attributes
		 * @return string
		 */
		public static function tag($name, array $attrs = array())
		{
			$o = '<'.$name.self::html_attrs($name, (array) $attrs).'>';

			if ($c = isset($attrs['content'])) {
				$o .= \System\Template::to_html(is_array($attrs['content']) ? implode('', $attrs['content']):$attrs['content']);
			}

			if ((($c && !isset($attrs['close'])) || (isset($attrs['close']) && $attrs['close'])) && !in_array($name, self::$noclose_tags)) {
				$o .= '</'.$name.'>';
			}

			if (!isset($attrs['output']) || $attrs['output'])
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

				if (is_array($attr)) {
					$separator = isset(self::$attr_separators[$name]) ? self::$attr_separators[$name]:'';
					$attr = implode($separator, $attr);
				}

				$is_valid = (!is_null($attr) && strlen($attr)) || $name == 'value';
				$available_for_tag = isset(self::$html_attrs[$tag]) && (self::$html_attrs[$tag] == '*' || in_array($name, self::$html_attrs[$tag]));
				$available_for_all_tags = in_array($name, self::$html_attrs['*']);
				$available_for_tag_class = in_array($name, self::get_tag_class($tag));

				if ($is_valid && ($available_for_all_tags || $available_for_tag || $available_for_tag_class)) {
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
