<?

namespace System
{
	abstract class Cli
	{
		private static $term_colors = array(
			'gray'   => "[1;30m",
			'light_red'   => "[1;31m",
			'light_green' => "[1;32m",
			'yellow'      => "[1;33m",
			'light_blue'  => "[1;34m",
			'magenta'     => "[1;35m",
			'light_cyan'  => "[1;36m",
			'white'       => "[1;37m",
			'normal'      => "[0m",
			'black'       => "[0;30m",
			'red'         => "[0;31m",
			'green'       => "[0;32m",
			'brown'       => "[0;33m",
			'blue'        => "[0;34m",
			'cyan'        => "[0;36m",
			'bold'        => "[1m",
			'underscore'  => "[4m",
			'reverse'     => "[7m",
		);


		/** Make text colored in terminal
		 * @param string $text
		 * @param string $color
		 * @param bool   $back
		 */
		public static function term_color($text, $color = "NORMAL")
		{
			$text = self::term_remove_color($text);
			$out = self::$term_colors[$color];
			$out == "" && $out = "[0m";

			return chr(27).$out.$text.chr(27)."[0m";
		}
		
		public static function term_remove_color($text)
		{
			$text = preg_replace('/'.chr(27).'\[[0-9];[0-9]*\m?/', '', $text);
			$text = preg_replace('/'.chr(27).'\[0\m/', '', $text);
			return $text;
		}
	}
}
