<?


namespace System
{
	abstract class Minifier
	{
		public static function process($type, $content)
		{
			return self::conversion_available($type) ? self::minify($type, $content):$content;
		}


		public static function conversion_available($type)
		{
			return class_exists(self::get_class_name($type));
		}


		private static function get_class_name($type)
		{
			return '\\System\\Minifier\\'.ucfirst($type);
		}


		public static function minify($type, $content)
		{
			$tool = self::get_class_name($type);
			return $tool::minify($content, array());
		}

	}
}
