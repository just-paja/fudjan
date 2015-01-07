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
					\System\Cli::out();
				}
			}
		}


		/** Iterate action over set of items and display progress
		 * @param array   $items  Set of items
		 * @param Closure $lambda Action to perform
		 * @return void
		 */
		public static function do_over(array $items, \Closure $lambda, $message = null, array &$extra = array(), $silent = false)
		{
			$total  = count($items);
			$x      = 0;
			$msglen = 35;

			if (!$silent && is_null($message)) {
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

				if (!$silent) self::progress($x++, $total, $msg, $msglen);
				$lambda($msg, $item, $extra);
				if (!$silent) self::progress($x, $total, $msg, $msglen);
			}
		}


		public static function lookup($name)
		{
			$cname = '\System\Cli\Module\\'.ucfirst($name);

			if (class_exists($cname)) {
				return new $cname();
			}

			return false;
		}


		public static function parse_command($argv)
		{
			$script = array_shift($argv);

			if (count($argv) > 0) {
				$module = array_shift($argv);

				if ($module != '-h' && $module != '--help') {
					$mod = self::lookup($module);

					if ($mod) {
						return $mod->run($argv);
					} else {
						self::give_up('Unknown module or option "'.$module.'"', 1);
					}
				}

				return self::show_usage($script);
			}

			self::show_info();
		}


		public static function show_usage($script)
		{
			self::out('Fudjan system manager');
			self::out();

			self::out('Usage: '.$script.' module [command] [params]');
			self::out('Use --help with command to see help for modules');
			self::out();

			\System\Loader::load_all();

			$all_classes = get_declared_classes();
			$child_classes = array();

			foreach ($all_classes as $class) {
				if (is_subclass_of('\\'.$class, '\System\Cli\Module')) {
					$ref = new \ReflectionClass($class);

					if (!$ref->isAbstract()) {
						$name = explode('\\', $class);
						$child_classes[] = strtolower($name[count($name) - 1]);
					}
				}
			}

			sort($child_classes);

			self::out('Modules:');
			self::out_flist(array(
				"list" => $child_classes,
				"show_keys" => false,
				"margin" => 4
			));
		}


		public static function show_info()
		{
			\System\Init::full();

			self::out_flist(array(
				"list" => array(
					"Version" => \System\Status::introduce(),
					"Environment" => \System\Settings::get_env()
				)
			));

			self::out();
			self::out("Loaded config files");
			self::sep();

			self::out_flist(array(
				"list" => \System\Settings::get_loaded_files(),
				"margin" => 2,
				"show_keys" => false
			));

			self::out();

			$db_list = cfg('database', 'list');

			foreach ($db_list as $db_ident => $db_cfg) {

				$size = \System\Database::query("
					SELECT
							sum(data_length + index_length) 'size',
							sum( data_free ) 'free'
						FROM information_schema.TABLES
						WHERE table_schema = '".$db_cfg['database']."'
						GROUP BY table_schema;
				")->fetch();

				$mlast_date = false;
				$mcount = 0;

				try {
					$mig = \System\Database\Migration::get_new();
					$mcount = count($mig);
					$mlast = get_first("\System\Database\Migration")->where(array("status" => 'ok'))->sort_by("updated_at DESC")->fetch();
					$stat = "Ok";

					if ($mlast) {
						$mlast_date = $mlast->updated_at;
					}
				} catch (System\Error $e) {
					$stat = "Migrating database is necessary.";
				}

				self::out('Database '.$db_ident);
				self::sep();

				self::out_flist(array(
					"list" => array(
						"Driver"         => $db_cfg['driver'],
						"Host name"      => $db_cfg['host'],
						"Database name"  => $db_cfg['database'],
						"User"           => $db_cfg['username'],
						"Used charset"   => $db_cfg['charset'],
						"Lazy driver"    => $db_cfg['lazy'] ? 'yes':'no',
						"Size"           => \System\Template::convert_value('information', $size['size']),
						"Free space"     => \System\Template::convert_value('information', $size['free']),
						"Structure"      => $stat,
						"Last migrated"  => $mlast_date ? $mlast_date->format('Y-m-d H:i:s'):'never',
						"New migrations" => $mcount,
					)
				));
			}

			self::out();
			self::out('Use --help for usage');
		}


		/** Print heading separator to STDOUT
		 * @param bool $return Return the value if true
		 * @return void
		 */
		public static function sep($return = false)
		{
			$str = '';
			for ($i = 0; $i <= \System\Cli::get_width()-1; $i++) {
				$str .= '-';
			}
			$str .= NL;

			if ($return) return $str; else echo $str;
		}


		/** Print a set of paired keys and values
		 * @param array  $list      Dictionary of values
		 * @param bool   $semicolon Add semicolon after keys
		 * @param int    $margin    Left margin of the list in char count
		 * @param bool   $return    Return the value if true
		 * @param string $key_color Color of keys
		 * @param bool   $purge     Skip empty values
		 * @return void
		 */
		public static function out_flist(array $data)
		{
			$list = def($data['list'], array());
			$semicolon = def($data['semicolon'], true);
			$margin = def($data['margin'], 0);
			$return = def($data['return'], false);
			$key_color = def($data['key_color'], 'normal');
			$purge = def($data['purge'], false);
			$show_keys = def($data['show_keys'], true);

			$str = '';
			$maxlen = 0;

			foreach ($list as $key => $value) {
				(strlen($key) > $maxlen) && $maxlen = strlen($key);
			}

			$maxlen ++;

			foreach ($list as $key => $value) {
				if (!$purge || ($purge && $value != '' && $value !== null)) {
					$output = '';

					if ($show_keys) {
						$output = $key.($semicolon ? ": ":" ");
						$output = self::term_color($output, $key_color);
					}

					$str .= self::out(str_repeat(" ", $margin).$output, false, true);

					if ($show_keys) {
						for ($padding = strlen($key); $padding < $maxlen - 1; $padding++) {
							$str .= self::out(" ", false, true);
						}
					}

					$str .= self::out($value, true, true);
				}
			}

			if ($return) return $str; else echo $str;
		}



		/** Kill script with return code
		 * @param string $str  Message to be printed
		 * @param int    $code Exit status
		 * @return int Return code
		 */
		public static function give_up($str, $code = 1)
		{
			echo $str.NL;
			exit($code);
		}


		/** Read from user input
		 * @param string Info for user input
		 * @param bool   Are we inputing password?
		 * @return string
		 */
		public static function read($str, $pass = false)
		{
			self::out($str, false);
			$val = trim(shell_exec("read ".($pass ? '-s ':'')."str; echo \$str"));
			$pass && print("\n");
			return $val;
		}


		/** Translate string containing something like 'yes' into bool
		 * @param string $str
		 * @return bool
		 */
		public static function is_yes($str)
		{
			return in_array(strtolower($str), array("yes", "y", "1", "a"));
		}


		/** Output to STDOUT
		 * @param string $str    String to print
		 * @param bool   $break  Print carriage return
		 * @param bool   $return Return the value if true
		 * @return void
		 */
		public static function out($str = '', $break = true, $return = false)
		{
			$str = $str.($break ? NL:'');
			if ($return) return $str; else echo $str;
		}
	}
}
