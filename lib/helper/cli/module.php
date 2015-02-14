<?

/** CLI core
 * @package core
 */
namespace Helper\Cli
{
	/** Container class for commands and options
	 * @package core
	 */
	class Module extends \System\Model\Attr
	{
		protected static $args;
		protected static $commands = array();
		protected static $accepts = 'command';


		/** Output to STDOUT if verbose
		 * @param string $str    String to print
		 * @param bool   $break  Print carriage return
		 * @param bool   $return Return the value if true
		 * @return void
		 */
		public function vout($str = '', $break = true, $return = false)
		{
			if ($this->verbose) {
				$str = \Helper\Cli::out($str, $break, $return);
				if ($return) return $str; else echo $str;
			}
		}


		public function get_name()
		{
			$name = explode('\\', get_class($this));
			return strtolower($name[count($name) - 1]);
		}


		/** Parse CLI arguments into options
		 * @return void
		 */
		public function parse_args($argv)
		{
			reset($argv);

			$attrs = $this::get_attr_def();
			$commands = array();

			while ($arg = array_shift($argv)) {
				if (strpos($arg, '-') !== false) {
					foreach ($attrs as $long => $info) {
						if (($arg == '--'.$long && $t = 'l') || (isset($info['short']) && $arg == '-'.$info['short'] && $t = 's')) {
							if ($info[0] == 'bool') {
								$this->$long = true;
							} else {
								switch ($info[0]) {
									case 'string':{

										if ($t == 's') {
											$value = array_shift($argv);

											if (any($value)) {
												$this->$long = $value;
											} give_up("Option ".$arg." requires value");
										} else {

											if (strpos($arg, '=') > 0) {
												list($key, $value) = explode('=', $arg, 2);

												if (any($value)) {
													$this->$long = $value;
												} else give_up("Option ".$arg." requires value");
											} else give_up("Option ".$arg." requires value");

										}
										break;
									}
								}
							}
						}
					}
				} else {
					$commands[] = $arg;
				}
			}

			return $commands;
		}


		public function command_exists($cmd)
		{
			return isset($this::$commands[$cmd]);
		}


		/** Display usage
		 * @return void
		 */
		public function usage()
		{
			$cmd_list = array();
			$opt_list = array();

			foreach ($this::$commands as $cmd => $info) {
				if (is_array($info)) {
					foreach ($info as $type => $desc) {
						$cmd_list[$type == 'single' ? $cmd:$cmd." '".$type."'"] = $desc;
					}
				} else $cmd_list[$cmd] = $info;
			}

			foreach ($this::$attrs as $opt => $info) {
				if (isset($info['desc'])) {
					$name = (isset($info['short']) ? '-'.$info['short'].' ':'   ').'--'.$opt;
					$opt_list[$name] = $info['desc'];
				}
			}

			\Helper\Cli::out(is_array($this::$info['head']) ? implode("\n", $this::$info['head']):$this::$info['head']);
			\Helper\Cli::out();


			\Helper\Cli::out("Usage:");
			\Helper\Cli::out("  ./".$this->get_name()." ".$this::$accepts);
			\Helper\Cli::out("  ./".$this->get_name()." ".$this::$accepts." [params]");
			\Helper\Cli::out();

			if (!empty($cmd_list)) {
				\Helper\Cli::out("Commands:");
				\Helper\Cli::out_flist(array(
					"list" => $cmd_list,
					"semicolon" => false,
					"margin" => 2
				));
				\Helper\Cli::out();
			}

			if (!empty($opt_list)) {
				\Helper\Cli::out("Options:");
				\Helper\Cli::out_flist(array(
					"list" => $opt_list,
					"semicolon" => false,
					"margin" => 2
				));
			}

			exit;
		}


		public function run($argv)
		{
			$commands = $this->parse_args($argv);

			if (any($commands) || $this->accepts('nothing')) {
				$cmd = array_shift($commands);

				if ($this->accepts('command')) {
					$this->cmd($cmd, $commands);
				} else {
					$this->run_for($cmd, $commands);
				}
			} else {
				return $this->usage();
			}
		}


		public function accepts($what)
		{
			return $this::$accepts == $what;
		}


		public function cmd($cmd, array $params = array())
		{
			if ($this->command_exists($cmd)) {
				$name = 'cmd_'.$cmd;

				if (method_exists($this, $name)) {
					$this->$name($params);
				} else \Helper\Cli::give_up("Command is not defined!", 3);
			} else \Helper\Cli::give_up("Please specify a valid command. Use --help option to get more info.", 2);

			return $this;
		}
	}
}
