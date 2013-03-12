<?

namespace System
{
	class Setup
	{
		private static $step = 'name';

		public static function init()
		{
			\System\Router::update_rewrite();
			\System\Output::set_template('pwf/setup');
		}


		public static function run()
		{
			if (method_exists('System\Setup', self::$step)) {
				$method = self::$step;
				return self::$method();
			}
		}


		public static function set_step($step_name)
		{
			return self::$step = $step_name;
		}


		public static function name()
		{
			$f = new \System\Form(array(
				"heading" => 'Purple Web Framework setup',
				"desc"    => 'This is your first run of pwf. Setup helps to fill basic configuration needed to run PWF. Fill in the required information and press save. You can edit settings in directory /etc/conf.d/{environment}. Default environment is "dev".',
			));

			$f->label('Basic site information');
			$f->input_text('name', 'Site name', true);
			$f->group_end();

			$f->label('Database information');

			$f->input(array(
				"name"     => 'database_ident',
				"label"    => l('Identificator'),
				"required" => true,
				"value"    => 'pwf',
				"type"     => 'text',
				"info"     => l('System identificator for this database'),
			));

			$f->input(array(
				"type"     => 'select',
				"name"     => 'database_driver',
				"label"    => l('Database driver'),
				"required" => true,
				"info"     => l('Type of database. You will most usually use MySQL here.'),
				"options"  => array(
					"MySQLi"      => 'mysqli',
					"PostgreSQL" => 'postgre',
				),
			));

			$f->input(array(
				"name"     => 'database_host',
				"label"    => l('Host name'),
				"required" => true,
				"value"    => 'localhost',
				"type"     => 'text',
				"info"     => l('Public hostname or IP address of machine where your database will be located'),
			));

			$f->input(array(
				"name"     => 'database_name',
				"label"    => l('Database name'),
				"required" => true,
				"value"    => 'pwf',
				"type"     => 'text',
				"info"     => l('How will you name your database'),
			));

			$f->input(array(
				"name"     => 'database_user',
				"label"    => l('User name'),
				"required" => true,
				"value"    => 'username',
				"type"     => 'text',
				"info"     => l('User name used to access database'),
			));

			$f->input(array(
				"name"     => 'database_pass',
				"label"    => l('Password'),
				"required" => true,
				"value"    => 'password',
				"type"     => 'text',
				"info"     => l('Password used to access database'),
			));

			$f->input(array(
				"name"     => 'database_lazy',
				"label"    => l('Lazy connect'),
				"checked"  => true,
				"type"     => 'checkbox',
				"info"     => l('The database driver will not connect until a query is sent'),
			));

			$f->input(array(
				"name"     => 'database_persistent',
				"label"    => l('Persistent connection'),
				"type"     => 'checkbox',
				"info"     => l('The database driver will open a persistent connection'),
			));

			$f->submit('Save configuration');

			if ($f->passed()) {
				$d = $f->get_data();

				$settings = array(
					"database" => array(
						"ident"      => $d['database_ident'],
						"driver"     => $d['database_driver'],
						"database"   => $d['database_name'],
						"host"       => $d['database_host'],
						"username"   => $d['database_user'],
						"password"   => $d['database_pass'],
						"lazy"       => $d['database_lazy'],
						"persistent" => $d['database_persistent'],
					),
					"seo" => array(
						"title" => $d['name'],
					),
				);

				try {
					Database::connect($settings['database'], $settings['database']['ident']);
				} catch (\System\Error\Database $e) {}

				if (!Database::is_ready($settings['database']['ident'])) {
					try {
						$instance = Database::get_db($d['database_ident']);
						$instance->create_database();
						Database::connect($settings['database'], $settings['database']['ident']);
					} catch (\System\Error $e) {
						v($e);exit;
					}
				}

				if (Database::is_ready($settings['database']['ident'])) {
					self::save($settings);
					$output = shell_exec("bin/db init");
				} else {
					$f->report_error('database_name', 'Could not connect to database');
					$f->out();
				}
			} else {
				$f->out();
			}
		}


		/** Save loaded settings
		 * @param array $data
		 */
		private static function save(array $data)
		{
			foreach (array('driver', 'database', 'host', 'username', 'password', 'lazy') as $key) {
				cfgs(array('database', 'list', $data['database']['ident'], $key), $data['database'][$key]);
			}

			cfgs(array('database', 'list', $data['database']['ident'], 'charset'), 'utf8');
			cfgs(array('database', 'list', $data['database']['ident'], 'is_yawf_home'), true);
			cfgs(array('database', 'default'), $data['database']['ident']);
			cfgs(array('database', 'connect'), array($data['database']['ident']));
			cfgs(array('default', 'title'), $data['seo']['title']);

			\System\Settings::save('database');
			\System\Settings::save('default');

			self::lock();
			self::finish();
		}


		/** There are no pages in page tree. Inform the user.
		 * @return void
		 */
		private static function no_pages()
		{
			echo '<h1>'.l('There are no routes').'</h1>';
			echo '<p>'.l('Setup has detected that there are no pages. Please define some pages using godmode utility or manually in file "/etc/conf.d/pages.json".').'</p>';
			echo '<p>'.l('It is recommended to checkout santa package manager first to download some addons. Also - did you create database yet?').'</p>';
		}


		/** Lock installer from running again
		 * @return void
		 */
		protected static function lock()
		{
			\System\File::put(ROOT.\System\Settings::DIR_CONF_ALL.'/install.lock', time());
		}


		/** Print out "install complete" message and exit
		 * @return void
		 */
		protected static function finish()
		{
			\Tag::h1(array("content" => l('Finished!')));
			\Tag::div(array(
				"content" => array(
					\Tag::p(array(
						"content" => sprintf(
							l('Your system should be ready to work. Check out your \'%s\' directory for other settings.'),
							ROOT.\System\Settings::DIR_CONF_DIST
						),
						"output"  => false,
					)),

					\Tag::p(array(
						"output"  => false,
						"content" => sprintf(
							l('Your next step could be installing modules via \'santa\' located in \'%s\''),
							ROOT.\Santa\Package::PATH_BIN
						),
					)),
				)
			));
		}
	}
}
