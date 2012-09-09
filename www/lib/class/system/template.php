<?

namespace System
{
	class Template
	{
		const TEMPLATES_DIR = '/lib/template';
		const PARTIALS_DIR = '/lib/template/partial';
		const DIR_ICONS = '/share/icons';
		const DEFAULT_SLOT = 'zzTop';
		const DEFAULT_ICON_THEME = 'default';

		const CASE_UPPER = MB_CASE_UPPER;
		const CASE_LOWER = MB_CASE_LOWER;

		private static $default_time_format = 'D, d M Y G:i:s e';
		private static $heading_level = 1;
		private static $heading_section_level = 1;

		private static $styles = array(
			array("name" => 'default', "type" => 'text/css'),
		);

		private static $units = array(
			"information" => array("B","kiB", "MiB", "GiB", "TiB", "PiB"),
		);


		public static function icon($icon, $size='32', array $attrs = array())
		{
			@list($width, $height) = explode('x', $size, 2);
			!$height && $height = $width;
			$icon = ($icon instanceof Image) ? $icon->thumb(intval($width), intval($height), !empty($attrs['crop'])):self::DIR_ICONS.'/'.$size.'/'.$icon.'.png';

			return '<span class="icon isize-'.$size.'" '.self::html_attrs('span', $attrs).'style="background-image:url('.$icon.'); width:'.$width.'px; height:'.$height.'px"></span>';
		}


		public static function get_filename($name, $format = null, $lang = null)
		{
			$format == 'xhtml' && $format = 'html';
			return $name.($lang ? '.'.$lang.'.':'').($format ? '.'.$format:'').'.php';
		}


		public static function partial($name, array $locals = array())
		{
			$temp = self::get_name($name);
			foreach ((array) $locals as $k=>$v) {
				$k = str_replace('-', '_', $k);
				$$k=$v;
			}

			return $temp ?
				include($temp):
				Status::log("out_partial", array("Partial not found: ".$name), false, true);
		}


		public static function insert($name, $locals = array(), $slot = self::DEFAULT_SLOT)
		{
			Output::add_template(array("name" => $name, "locals" => $locals), $slot);
		}


		public static function get_name($name)
		{
			$base = ROOT.self::PARTIALS_DIR.'/';
			$f = '';

			file_exists($f = $base.self::get_filename($name, Output::get_format(), Output::get_lang())) ||
			file_exists($f = $base.self::get_filename($name, Output::get_format())) ||
			file_exists($f = $base.self::get_filename($name)) ||
			$f = '';

			return $f;
		}


		public static function meta_out()
		{
			Output::content_for("meta", array("name" => 'generator', "content" => Output::introduce()));
			Output::content_for("meta", array("name" => 'generated-at', "content" => self::format_time('std')));
			Output::content_for("meta", array("http-equiv" => 'content-type', "content" => Output::get_format(true).'; charset=utf-8'));

			$meta = Output::get_content_from("meta");
			foreach ($meta as $name=>$value) {
				if ($name && $value) {
					Output::content_for("head", '<meta'.self::html_attrs('meta', $value).' />');
				}
			}
		}


		public static function scripts_out()
		{
			Output::content_for("head", '<script type="text/javascript" src="/share/scripts/'.implode(':', Output::get_content_from("scripts")).'"></script>');
		}


		public static function styles_out()
		{
			Output::content_for("head", '<link type="text/css" rel="stylesheet" href="/share/styles/'.implode(':', Output::get_content_from("styles")).'" />');
		}


		public static function title_out()
		{
			Output::content_for("head", '<title>'.Output::get_title().'</title>');
		}


		public static function head_out()
		{
			self::meta_out();
			self::title_out();
			self::styles_out();
			self::scripts_out();
		}


		public static function link_for($label, $url, $object = array())
		{
			if (!is_array($object)) {
				$object = array("no-tag" => !!$object);
			}

			!isset($object['no-tag']) && $object['no-tag'] = false;
			!isset($object['strict']) && $object['strict'] = false;
			!isset($object['no-activation']) && $object['no-activation'] = false;
			!isset($object['class']) && $object['class'] = '';

			$path = Page::get_path();
			clear_this_url($url); clear_this_url($path);
			$object['class'] = explode(' ', $object['class']);

			$is_root     = $url == '/' && $path == '/';
			$is_selected = $url && !$object['no-activation'] && (($object['strict'] && ($url == $path || $url == $path.'/')) || (!$object['strict'] && strpos($path, $url) === 0));

			if ($is_root || ($url != '/' && $is_selected)) {
				$object['class'][] = 'link-selected';
			}

			$object['class'] = implode(' ', $object['class']);

			return (($object['no-tag'] && $path == $url) ?
					'<span class="link'.($object['class'] ? ' '.$object['class']:NULL).'">':
					'<a href="'.$url.'"'.self::html_attrs('a', $object).'>'
				).$label.
				(($object['no-tag'] && $path == $url) ? '</span>':'</a>');
		}


		public static function icon_for($icon, $size=32, $url, $label = NULL, $object = array())
		{
			$object['title'] = $label;
			def($object['label'], '');
			return self::link_for(self::icon($icon, $size).'<span class="lt">'.($object['label']?$label:'').'</span>', $url, $object);
		}


		public static function heading($label, $save_level = true, $level = NULL)
		{
			if ($level === NULL) {
				$level = self::$heading_level+1;
			}

			if ($save_level) {
				self::set_heading_level($level);
				if ($level == 1) {
					self::$heading_section_level = 2;
				}
			}

			$tag = ($level > 6) ? 'strong':'h'.$level;
			$attrs = array(
				"id" => \System\Model\Basic::gen_seoname($label)
			);
			return self::tag($tag, $label, $attrs);
		}


		public static function tag($tag, $content = '', array $attrs = array())
		{
			return '<'.$tag.' '.self::html_attrs($tag, $attrs).'>'.$content.'</'.$tag.'>';
		}


		public static function section_heading($label, $level = NULL)
		{
			if ($level === NULL) {
				$level = self::$heading_section_level == 1 ? self::$heading_section_level++:self::$heading_section_level;
			}

			self::set_heading_level($level);

			return heading($label, true, $level);
		}


		public static function get_heading_level()
		{
			return self::$heading_level;
		}


		public static function set_heading_level($lvl)
		{
			return self::$heading_level = intval($lvl);
		}


		public static function set_heading_section_level($lvl)
		{
			return self::$heading_section_level = intval($lvl);
		}


		public static function format_time($format = "std", $datetime = NULL)
		{
			if (is_numeric($datetime)) {
				$obj = new Datetime();
				$obj->setTimestamp($datetime);
			} else {
				$obj = $datetime instanceof \Datetime ? $datetime:new \Datetime($datetime);
			}

			$format = Locales::get('date:'.$format) ? Locales::get('date:'.$format):self::$default_time_format;

			if (strpos($format, 'human') !== false) {
				$f = str_split($format, 1);
				$str = '';
				foreach ($f as $l) {
					$str .= $obj->format($l);
				}
				return $str;
			} else {
				return $obj->format($format);
			}
		}


		public static function get_css_color($color)
		{
			if ($color instanceof ColorModel) {
				$c = $color->get_color();
			} elseif (is_array($color)) {
				$c = $color;
			} else {
				throw new \InvalidArgumentException("Template::get_color_container: arg 0 must be instance of ColorModel or set of color composition");
			}

			return is_null($c[3]) ?
				'rgb('.$c[0].','.$c[1].','.$c[2].')':
				'rgba('.$c[0].','.$c[1].','.$c[2].','.str_replace(",", ".", floatval($c[3])).')';
		}


		public static function get_color_container($color)
		{
			if ($color instanceof ColorModel) {
				$c = $color->get_color();
			} elseif (is_array($color)) {
				$c = $color;
			} else {
				throw new \InvalidArgumentException("Template::get_color_container: arg0 must be instance of ColorModel or set of color composition");
			}

			return '<span class="color-container" style="background-color:'.self::get_css_color($c).'"></span>';
		}



		static function convert_value($type, $value)
		{
			switch($type){
				case 'information':
					$step = 1024;
					break;
				default:
					$step = 1000;
					break;
			}

			for($i=0; $value>=1024; $i++){
				$value /= 1024;
			}
			return round($value, 2)." ".self::$units[$type][$i];
		}


		/** Get configured or default icon theme
		 * @return string
		 */
		static function get_icon_theme()
		{
			$theme = cfg('icons', 'theme');
			return $theme ? $theme:self::DEFAULT_ICON_THEME;
		}
	}
}
