<?

namespace System
{
	abstract class Cli
	{
		private static $width = 60;
		private static $height = 40;

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


		public static function init()
		{
			self::checkout_console_size();
		}


		public static function term_remove_color($text)
		{
			$text = preg_replace('/'.chr(27).'\[[0-9];[0-9]*\m?/', '', $text);
			$text = preg_replace('/'.chr(27).'\[0\m/', '', $text);
			return $text;
		}


		public static function checkout_console_size()
		{
			preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a |grep columns')), $output);

			if (sizeof($output) == 3) {
				self::$width = $output[2][0];
				self::$height = $output[1][0];
			}
		}


		public static function get_width()
		{
			return self::$width;
		}


		public static function get_height()
		{
			return self::$height;
		}


		public static function try_shell($cmd, &$output = null, &$exit_status = null)
		{
			if (!($banned = preg_match('/(^|[,\ ])exec([,\ ]|$)/', ini_get('disable_functions')))) {
				exec($cmd, $output, $exit_status);
			}

			return !$banned;
		}
	}
}
