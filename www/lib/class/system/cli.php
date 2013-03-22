<?

/** @package system
 */
namespace System
{
	/** Container class encapsulating methods used on console
	 */
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
			preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a 2> /dev/null | grep columns')), $output);

			if (sizeof($output) == 3) {
				self::$width = def($output[2][0], 0);
				self::$height = def($output[1][0], 0);
			}
		}


		public static function get_width()
		{
			self::checkout_console_size();
			return self::$width;
		}


		public static function get_height()
		{
			self::checkout_console_size();
			return self::$height;
		}


		public static function try_shell($cmd, &$output = null, &$exit_status = null)
		{
			if (!($banned = preg_match('/(^|[,\ ])exec([,\ ]|$)/', ini_get('disable_functions')))) {
				exec($cmd, $output, $exit_status);
			}

			return !$banned;
		}


		/** Show progress bar on cli
		 * @param int    $done       Count of items, that are done
		 * @param int    $total      Total count of items
		 * @param int    $size       Width of progress bar in chars
		 * @param string $done_msg   Message that will be printed on finish
		 * @param string $static_msg Message that will be printed during the process
		 * @return void
		 */
		public static function progress($done, $total, $msg = '', $msglen = null)
		{
			if ($done <= $total) {
				$size   = self::get_width();
				$msglen = is_null($msglen) ? strlen($msg):$msglen;
				$psize  = abs($size - $msglen - 12);

				$perc = (double) ($done/$total);
				$bar  = floor($perc*$psize);

				$status_bar = "\r  [";
				$status_bar .= str_repeat("=", $bar);

				if ($bar < $psize) {
					$status_bar .= ">";
					$status_bar .= str_repeat(" ", $psize - $bar -1);
				} else {
					$status_bar.="=";
				}

				$disp = number_format($perc*100, 0);
				$status_bar .= "] $disp%";

				$left = $total - $done;
				$status_bar .= ": ".$msg;

				if (($blank = $size - strlen($status_bar)) > 0) {
					$status_bar .= str_repeat(' ', $blank);
				}

				echo $status_bar;
				flush();

				if($done == $total) {
					out();
				}
			}
		}


		/** Iterate action over set of items and display progress
		 * @param array   $items  Set of items
		 * @param Closure $lambda Action to perform
		 * @return void
		 */
		public static function do_over(array $items, \Closure $lambda, $message = null, array $extra = array())
		{
			$total  = count($items);
			$x      = 0;
			$msglen = 35;

			if (is_null($message)) {
				foreach ($items as $msg=>$item) {
					if (($m = strlen($msg)) > $msglen) {
						$msglen = $m;
					}
				}
			} else {
				if (($m = strlen($message)) > $msglen) {
					$msglen = strlen($message);
				}
			}

			// Right margin
			$msglen += 2;

			foreach ($items as $msg=>$item) {
				$msg = is_null($message) ? $msg:$message;

				self::progress($x++, $total, $msg, $msglen);
				$lambda($msg, $item, $extra);
				self::progress($x, $total, $msg, $msglen);
			}
		}
	}
}
