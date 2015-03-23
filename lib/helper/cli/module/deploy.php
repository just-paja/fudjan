<?

namespace Helper\Cli\Module
{
	class Deploy extends \Helper\Cli\Module
	{
		protected static $accepts = 'environment';
		protected static $info = array(
			'name' => 'assets',
			"head" => array(
				'Manage bower assets',
				'Read settings from your environment and perform actions for assets'
			),
		);


		protected static $attrs = array(
			"help"       => array("type" => 'bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"verbose"    => array("type" => 'bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
		);


		public function run_for($env)
		{
			if (!\System\Settings::env_exists($env)) {
				$response = \Helper\Cli::read('Environment "'.$env.'" does not exist. Create? [Y/n] ');

				if (\Helper\Cli::is_yes($response)) {
					\System\Settings::env_create($env);
				}
			}

			return $this->deploy($env);
		}


		/** Run the deploy control process
		 * @return void
		 */
		private function deploy($env)
		{
			$proceed = null;
			$attempts = 0;
			\System\Settings::set_env($env);

			do {
				$cfg = $this->read_info($proceed !== NULL);

				\Helper\Cli::sep();
				\Helper\Cli::out("Please confirm, that these information are correct");

				if ($cfg['protocol'] == 'file') {
					$cfg['host'] = trim(shell_exec('uname -n'));
				}

				$this->print_info($cfg);
				\Helper\Cli::out();
				$proceed = \Helper\Cli::is_yes(\Helper\Cli::read("Do you wish to proceed? [Y/n]: "));

				if (!$proceed) {
					\Helper\Cli::sep();
					\Helper\Cli::out("Not confirmed, rereading settings.");
				}

				$attempts ++;
			} while (!$proceed && $attempts < 3);

			if ($proceed) {
				$deploy_method = 'deploy_over_'.$cfg['protocol'];
				$this->save_config($cfg, $env);

				if (method_exists($this, $deploy_method)) {
					\Helper\Cli::out();
					self::$deploy_method($cfg, $this->get_file_list());
				} else {
					\Helper\Cli::give_up("Your method of deployment has not yet been implemented");
				}
			} else \Helper\Cli::give_up("Giving up after being trolled 3 times");

			\Helper\Cli::out();
		}


		/** Try to read necessary info
		 * @return array
		 */
		private function read_info($reread_cfg = false)
		{
			$this->vout("Checking for stored login information");
			\System\Settings::reload();
			$cfg = array();

			if ($p = cfg('deploy', 'protocol')) {
				$cfg = array(
					"protocol" => $p,
					"host"     => cfg('deploy', $p.'_host'),
					"user"     => cfg('deploy', $p.'_user'),
					"pass"     => cfg('deploy', $p.'_pass'),
					"root"     => cfg('deploy', $p.'_root'),
				);
			}

			if ($reread_cfg || empty($cfg['host']) || empty($cfg['user'])) {
				$cfg = $this->read_user_info($cfg);
			}

			return $cfg;
		}


		/** Ask user to enter info
		 * @return array Assoc array of info
		 */
		private function read_user_info(array $cfg)
		{
			$keys = array(
				"protocol" => array(
					"label"   => 'Please choose protocol',
					"type"    => 'string',
					"options" => array('file', 'ssh', 'ftp'),
				),
				"host"     => array(
					"label" => 'Enter valid hostname',
					"type"  => 'string'
				),
				"user"    => array(
					"label" => 'Enter username',
					"type"  => 'string',
				),
				"pass" => array(
					"label" => 'Enter valid password',
					"type"  => 'password'
				),
				"root" => array(
					"label" => 'Enter document root (where index.php will be)',
					"type"  => 'string'
				),
			);
			$data = array();

			foreach ($keys as $key=>$params) {
				while (!isset($data[$key])) {
					$val = null;
					$label = $params['label'];

					if (any($params['options'])) {
						$label .= ' ('.implode(', ', $params['options']).')';
					}

					if (any($cfg[$key]) && $params['type'] != 'password') {
						$label .= ' ['.$cfg[$key].']';
					}

					$val = \Helper\Cli::read($label.': ', $params['type'] == 'password');

					if (!isset($params['options']) || in_array($val, $params['options'])) {
						$data[$key] = $val;
					}

					if (empty($data[$key]) && any($cfg[$key])) {
						$data[$key] = $cfg[$key];
					}
				}
			}

			return $data;
		}



		/** Display process info to STDOUT
		 * @return void
		 */
		private function print_info($cfg)
		{
			\Helper\Cli::out_flist(array(
				"list" => array(
					"Used protocol" => $cfg['protocol'],
					"Hostname"      => $cfg['host'].($cfg['protocol'] == 'file' ? ' (forced)':''),
					"User"          => $cfg['user'],
					"Remote root"   => $cfg['root']
				)
			));
		}


		/** Save deploy configuration
		 * @param array $cfg Current config
		 * @return void
		 */
		private function save_config(array $cfg, $env)
		{
			$this->vout("Deploy information has been saved");
			cfgs(array("deploy", 'protocol'), $cfg['protocol']);
			cfgs(array("deploy", $cfg['protocol'].'_host'), $cfg['host']);
			cfgs(array("deploy", $cfg['protocol'].'_user'), $cfg['user']);
			cfgs(array("deploy", $cfg['protocol'].'_pass'), $cfg['pass']);
			cfgs(array("deploy", $cfg['protocol'].'_root'), $cfg['root']);

			\System\Settings::save("deploy", $env);
		}


		/** Get list of all files ready to deploy
		 * @param string $dirpath ROOT
		 * @return array
		 */
		private function get_file_list($dirpath = BASE_DIR)
		{
			$ommited_paths = array(
				'/var',
				'/lib/vendor',
				'/share/bower',
			);

			$files = array();
			$dirpath_diff = substr($dirpath, strlen(BASE_DIR));
			$skip = false;

			if (is_dir($dirpath) && ($dir = opendir($dirpath))) {
				while ($file = readdir($dir)) {
					if (strpos($file, ".") !== 0 || $file == '.htaccess') {

						foreach ($ommited_paths as $op) {
							if (strpos($dirpath_diff, $op) === 0) {
								$skip = true;
								break;
							}
						}

						if (!$skip) {
							if (is_file($p = realpath($dirpath).'/'.$file)) {
								$files[] = $dirpath_diff.'/'.$file;
							} else {
								$files = array_merge($files, self::get_file_list($p));
							}
						} else $skip = false;
					}
				}
			}

			return $files;
		}


		/** Deploy application using local file transfer
		 * @param array $cfg   Configuration
		 * @param array $files List of all files
		 */
		private function deploy_over_file(array $cfg, array $files)
		{
			\Helper\Cli::out();
			$this->vout("Copying files to another location ..");

			!is_dir($cfg['root']) && mkdir($cfg['root'], 0775, true);

			\Helper\Cli::do_over($files, function($number, $file, $cfg) {
				$fdir = dirname($file);
				\System\Directory::check($cfg['root'].'/'.$fdir);
				copy(BASE_DIR.'/'.$file, $cfg['root'].'/'.$file);
			}, null, $cfg);
		}


		/** Deploy using good old file transfer protocol
		 * @param array $cfg
		 * @param array $files
		 */
		private function deploy_over_ftp(array $cfg, array $files)
		{
			exec("echo \$PATH", $o);
			exec("whoami", $whoami);
			$reqs = false;
			$o = explode(':', $o[0]);
			$whoami = implode("", $whoami);
			$total = count($files);
			$dir_spool = "/home/".$whoami."/.ncftp/spool";

			foreach ($o as $path) {
				if (file_exists($path.'/ncftpls')) {
					$reqs = true;
					break;
				}
			}

			if ($reqs) {
				$this->vout("Copying files over FTP ..");
				if ($this->try_ftp_login($cfg['host'], $cfg['user'], $cfg['pass'])) {
					$x = 0;

					$this->vout("Deleting old queue files ..");
					exec("rm -R ".$dir_spool." &> /dev/null");

					\Helper\Cli::do_over($files, function($num, $file, $cfg) {
						$local = BASE_DIR.$file;
						$remote = dirname($cfg['root'].substr($file, 1));
						exec('ncftpput -bb -u "'.$cfg['user'].'" -p"'.$cfg['pass'].'" "'.$cfg['host'].'" "'.$remote.'" "'.$local.'" >& /dev/null');
					}, "Adding files to FTP queue", $cfg);

					$msg = "Uploading files";
					\Helper\Cli::progress(0, $total, NULL, $msg);
					$ph = popen("ncftpbatch -D", 'r');

					while (!feof($ph) && $line = fgets($ph)) {
						if (strpos($line, "Done") === 0) {
							\Helper\Cli::progress(++$x, $total, $msg);
						}
					}

					pclose($ph);

				} else give_up("Failed to login over FTP.");
			} else give_up("Please install ncftp");

		}


		private static function try_ftp_login($host, $user, $pass)
		{
			return trim(shell_exec("ncftpls -u ${user} -p ${pass} ftp://${host}; echo $?")) == 0;
		}
	}
}
