<?

namespace Helper\Cli\Module
{
	class Db extends \Helper\Cli\Module
	{
		protected static $info = array(
			'name' => 'db',
			"head" => array(
				'Modify PWF database',
				'Read settings from your environment and perform action'
			),
		);


		protected static $attrs = array(
			"help"       => array("type" => 'bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
			"force"      => array("type" => 'bool', "value" => false, "short" => 'f', "desc"  => 'Use the Force!'),
			"skip"       => array("type" => 'bool', "value" => false, "desc" => 'Mark first migration ok and skip it'),
			"no_backups" => array("type" => 'bool', "value" => false, "desc" => 'Make no backups during the process'),
			"json"       => array("type" => 'bool', "value" => false, "short" => 'j', "desc" => 'Output in json. Works with db dump functions.'),
			"verbose"    => array("type" => 'bool', "value" => false, "short" => 'v', "desc" => 'Be verbose'),
			"structure"  => array("type" => 'bool', "value" => false, "short" => 's', "desc" => 'Structure', "default" => true),
			"relations"  => array("type" => 'bool', "value" => false, "short" => 'e', "desc" => 'Relations', "default" => true),
		);

		protected static $commands = array(
			"dump" => array(
				'single' => "Generate database backup to STDOUT",
				'path'   => "Save backup into file"
			),
			"drop"     => 'Drop database',
			"init"     => 'Create basic database with initial data',
			"migrate" => array(
				'single' => "Process new migrations",
				'search' => "Process migrations like [param]",
			),
			"migrations" => array(
				'single' => "List migrations",
				'search' => "List migrations like [param]",
			),
			"rebuild"  => 'Drop database and rebuild it from scratch',
			"reset"    => 'Reset database to default state',
			"restore"  => array(
				"single" => 'Check for saved backups and restore database',
				"path" => 'Read sql file and import it',
			),
			"save"     => 'Save database backup into standart path',
			"seed"     => 'Fill database with initial data if empty',
			"sync"     => array(
				"single"    => 'Sync database structure and contents',
				"structure" => 'Sync database structure',
				"relations" => 'Sync foreign keys',
			),
		);




		/** Sync database structure and contents
		 */
		public function cmd_sync(array $params = array())
		{
			\Helper\Cli::out('Running structural sync');

			$this->structure && $this->sync_structure();
			$this->relations && $this->sync_relations();
		}


		/* List available migrations
		 * @return void
		 */
		public function cmd_migrations(array $params = array())
		{
			\System\Init::full();

			if (empty($params)) {
				$mig = \System\Database\Migration::get_new();
				array_reverse($mig);
				$this->print_migrations($mig);
			} else {
				$mig = self::find_migrations($params[0]);

				if (any($mig)) {
					$this->vout("Found ".count($mig)." migration".(count($mig) == 1 ? '':'s').":");
					$this->vout("");
					$this->print_migrations($mig);
				}
			}
		}


		/* Restore database from backup
		 * @return void
		 */
		public function cmd_restore(array $params = array())
		{
			\System\Init::basic();

			if (isset($params[0])) {
				if (file_exists($params[0])) {
					$db_ident = cfg('database', 'default');
					$db_name = cfg('database', 'list', $db_ident, 'database');
					$this->vout("Restoring database '".$db_name."'");

					if (!self::database_exists()) {
						$this->database_create();
					}

					if ($this->json) {
						$this->import_json($params[0]);
					} else {
						$this->import_sql($params[0]);
					}

					\Helper\Cli::out("Database restored");
				} else give_up("File not found!", 5);
			} else {

				$backups = array();
				$files   = array();

				$d = opendir(BASE_DIR."/var/backups");
				while ($f = readdir($d)) {
					if (strpos($f, ".") !== 0 && strpos($f, ".sql")) {
						$name = explode('_', $f);
						$backups[] = $name[0];
						$files[] = $f;
					}
				}

				rsort($backups);
				rsort($files);

				if (any($backups)) {
					\Helper\Cli::out("Found these backups:");
					\Helper\Cli::out_flist(array(
						"list" => $backups,
						"margin" => 2
					));

					\Helper\Cli::out('');

					$key = $this->force ? 0:\Helper\Cli::read("Pick one [0-".count($backups)."]: ", false);

					if (array_key_exists($key, $backups)) {
						if ($this->database_exists()) {
							$this->cmd('drop');
						}

						$this->database_create();

						\Helper\Cli::out("Restoring to: ".$backups[$key]);
						self::import_sql(BASE_DIR."/var/backups/".$files[$f]);
						\Helper\Cli::out("Database restored");

					} else give_up("Invalid option.");
				} else give_up("Did not find any backups");
			}
		}


		public function sync_structure()
		{
			\System\Init::full();
			\System\Loader::load_all();

			$models = \System\Model\Database::get_all_children();
			$msg_done = NULL;
			$msg_work = 'Syncing database structure';

			\Helper\Cli::do_over($models, function($key, $name) {
				\Helper\Database\Structure::sync_model($name);
			});
		}


		public function sync_relations()
		{
			//~ \Helper\Cli::out('Syncing foreign keys is not implemented yet');
		}


		/* Get mysql dump
		 * @outputs a lot of crap
		 * @return void
		 */
		public function cmd_dump(array $params = array())
		{
			\System\Init::full();

			$file = array_shift($params);

			if ($this->json) {

				\System\Loader::load_all();
				$models = \System\Model\Database::get_all_children();
				$data = array();

				\Helper\Cli::do_over($models, function($key, $model, &$data) {
					$objects = $model::get_all()->fetch();

					if (any($objects)) {
						$data[$model] = \System\Template::to_json($objects, false);
					}
				}, null, $data, !is_null($file));

				if ($file) {
					\System\Json::put($file, $data);
				} else {
					echo json_encode($data);
				}
			} else {
				$cmd = self::assemble_mysql_command("mysqldump", true);

				if ($file) {
					shell_exec("mkdir -m775 -p ".dirname($file));
					$cmd .= " > ".$file;
				}

				passthru($cmd);
			}
		}


		/* Put together mysql call using options
		 * @return string
		 */
		private static function assemble_mysql_command($cmd, $db = false)
		{
			$db_ident = cfg('database', 'default');
			$db_cfg = cfg('database', 'list', $db_ident);

			$db_cfg['password'] && $cmd .= " -p".$db_cfg['password'];
			$db_cfg['username'] && $cmd .= " -u ".$db_cfg['username'];
			$db_cfg['host']     && $cmd .= " -h ".$db_cfg['host'];
			$db && $cmd .= ' '.$db_cfg['database'];

			return $cmd;
		}


		/* Drop whole database structure
		 * @return void
		 */
		public function cmd_drop()
		{
			\System\Init::full();

			if (self::database_exists()) {

				\Helper\Cli::out("Are you sure you want to get rid of database? (yes/no) ", false);
				$str = trim(shell_exec("read str; echo \$str"));

				if (\Helper\Cli::is_yes($str)) {
					$db_ident = cfg('database', 'default');
					$db_name = cfg('database', 'list', $db_ident, 'database');

					self::save();
					\Helper\Cli::out("Dropping database '".$db_name."'");
					$cmd = "echo \"DROP DATABASE \`".$db_name."\`\" | ".self::assemble_mysql_command('mysql');
					passthru($cmd);

					$this->vout("She's dead, Jim.");
				}
			} else {
				$this->vout('Database does not exist. Cannot drop it.');
			}
		}


		/* Does db exist
		 * @return bool
		 */
		private static function database_exists()
		{
			$db_ident = cfg('database', 'default');
			$db_name  = cfg('database', 'list', $db_ident, 'database');

			$query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$db_name."'";
			$cmd = "echo \"".$query."\" | ".self::assemble_mysql_command("mysql", false);
			$res = shell_exec($cmd);
			return !is_null($res);
		}



		/* Save backup of database into standart path
		 * @return void
		 */
		public function save()
		{
			if (!$this->no_backups) {
				$db_ident = cfg('database', 'default');
				$db_name  = cfg('database', 'list', $db_ident, 'database');

				$file = BASE_DIR."/var/backups/".str_replace(" ", "-", date("Y-m-d H:i:s"))."_".$db_name.".sql";
				\Helper\Cli::out("Creating backup of '".$db_name."' in '".$file."'");
				$this->backup($file);
			}
		}


		public function backup($file = null)
		{
			return $this->cmd_dump($file ? array($file):array());
		}


		/* Create database and fill it with initial data
		 * @return void
		 */
		public function cmd_init(array $params = array())
		{
			$this->database_create();
			$this->cmd_sync();
			$this->database_init();

			\Helper\Cli::out();
			$this->cmd_migrate();
			\Helper\Cli::out();

			$this->cmd_seed();
		}


		/* Create db
		 * @return void
		 */
		private function database_create()
		{
			\System\Init::basic();

			if (!self::database_exists()) {
				$db_ident = cfg('database', 'default');
				$name     = cfg('database', 'list', $db_ident, 'database');
				$cmd = "echo \"CREATE DATABASE ".$name."\" | ".self::assemble_mysql_command("mysql", false);

				\Helper\Cli::out("Creating database '".$name."'");
				shell_exec($cmd);
			}

			return $this;
		}


		/* Create basic DB structure for saving migrations if not exists
		 * @return void
		 */
		private function database_init()
		{
			\System\Init::full();

			$db_ident = cfg('database', 'default');
			$db_cfg = cfg('database', 'list', $db_ident);
			return $this;
		}



		/* Proceed with migrations
		 * @return void
		 */
		public function cmd_migrate(array $params = array())
		{
			\System\Init::full();

			if (empty($params)) {

				$mig = \System\Database\Migration::get_new();
				array_reverse($mig);

				if (any($mig)) {
					$this->process_migrations($mig);
				} else $this->vout("Did not find any usable migrations");

			} else {

				$mig = $this->find_migrations($params[0]);

				if (any($mig)) {

					\Helper\Cli::out("Found ".count($mig)." migration".(count($mig) == 1 ? '':'s').":");
					$this->print_migrations($mig, $padding = 2);
					\Helper\Cli::out("");

					\Helper\Cli::out("Do you wish to proceed? (y/n) ", false);
					$str = trim(shell_exec("read str; echo \$str"));

					if (in_array(strtolower($str), array("y", "1", "a"))) {
						$this->save();
						$this->process_migrations($mig);
					}

				} else \Helper\Cli::out("Did not match any migration");
			}
		}


		/* Run migrations on list
		 * @param array $mig  Set of migrations
		 * @return void
		 */
		private function process_migrations(array $mig)
		{
			foreach ($mig as $m) {
				$this->vout("Starting migration ".$m->md5_sum." ..");

				if ($this->skip) {
					$this->skip = false;
					$m->status = 'ok';
					$m->save();
				} else {
					if ($m->status == 'ok' && !$this->force) {
						$m->status = 'skipped ('.$m->status.')';
					} else {
						$m->run();
					}
				}

				$this->verbose ?
					$this->print_migrations(array($m)):
					\Helper\Cli::out('  '.$m->md5_sum.': '.$m->status);
			}
		}



		/* Prepared search statement
		 * @param string $like String to match with
		 * @return array      Set of matches migrations
		 */
		private function find_migrations($like)
		{
			$this->vout("Looking for migration described as ".$like." ..");
			return System\Database\Migration::get_all()->where(array(
					"`id_system_database_migration` LIKE '%".$like."%'",
					"`name` LIKE '%".$like."%'",
					"`seoname` LIKE '%".$like."%'",
					"`md5_sum` = '".$like."'",
				), 't0', true)->fetch();
		}


		/* Print migrations to STDOUT
		 * @param array $mig   Set of migrations
		 * @param int $padding Left padding of the list
		 * @return void
		 */
		private function print_migrations(array $mig, $padding = 0)
		{
			$pad = str_repeat(' ', $padding);

			foreach ($mig as $m) {
					\Helper\Cli::out($pad.'['.$m->date->format('Y-m-d').'] '.$m->name);
					$this->vout($pad.'     Status: '.$m->status);
					$this->vout($pad.'    MD5 sum: '.$m->md5_sum);
					$this->vout($pad.'       Desc: '.$m->desc);
					$this->vout('');
			}
		}


		/* Seed initial data to DB
		 * @return void
		 */
		public static function cmd_seed(array $params = array())
		{
			\System\Init::full();
			\Helper\Cli::out('Seeding initial system data ..');

			\System\Database::seed_initial_data();
			$data = \System\Settings::read(\System\Database::DIR_INITIAL_DATA, true);

			if ($data) {
				\Helper\Cli::out("Injecting initial data ..");

				foreach ($data as $data_set_name => $data_set_models) {
					self::seed_data($data_set_name, $data_set_models);
				}
			}
		}


		private static function seed_data($data_set_name, $data_set_models)
		{
			\System\Init::full();

			foreach ($data_set_models as $model => $data_set) {

				$extra = array("model" => $model);

				\Helper\Cli::do_over($data_set, function($key, $tdata, $extra) {
					$model = $extra['model'];

					$obj = null;
					$idc = $model::get_id_col($model);

					if (isset($tdata[$col = 'id']) || isset($tdata[$col = $idc])) {
						$tdata[$idc] = $tdata[$col];
						$obj = $model::find($tdata[$col]);
					}

					if ($obj) {
						$obj->update_attrs($tdata);
					} else {
						$obj = new $model($tdata);
						$obj->is_new_object = true;
					}

					$obj->save();

					foreach ($tdata as $attr=>$val) {
						if (is_array($val) && $model::is_rel($attr)) {
							if ($model::get_attr_type($attr) == $model::REL_HAS_MANY) {
								$def = $model::get_attr($attr);

								if (any($def['is_bilinear']) && any($def['is_master'])) {
									unset($obj->$attr);
									$obj->$attr = $val;
								}
							}
						}
					}
				}, $data_set_name.': '.\System\Loader::get_model_from_class($model), $extra);
			}
		}



		/* Drop whole database structure and rebuild it
		 * @return void
		 */
		public function cmd_rebuild()
		{
			$this
				->cmd('drop')
				->database_create()
				->database_init()
				->cmd('sync')
				->cmd('migrate');
		}


		/* Reset database to default state
		 * @return void
		 */
		public function cmd_reset()
		{
			$this->cmd_rebuild();
			\Helper\Cli::out();
			$this->cmd_seed();

			return $this;
		}



		/* Import SQL file into database
		 * @param string $file Path to file
		 * @return void
		 */
		private function import_sql($file)
		{
			if (file_exists($file)) {
				shell_exec(self::assemble_mysql_command("mysql", true). " < ".$file);
			} else give_up("File not found: ".$file);

			return $this;
		}


		private function import_json($file)
		{
			$json = \System\Json::read($file);
			$this->seed_data($file, $json);
			return $this;
		}
	}
}
