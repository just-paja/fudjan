<?

namespace System
{
	class Setup
	{
		private static $step = 'name';

		public static function init()
		{
			Output::set_template('setup');
		}


		public static function run()
		{
			if (method_exists('System\Setup', self::$step)) {
				$method = self::$step;
				return self::$method();
			}
		}


		public static function name()
		{
			$f = new \System\Form(array(
				"heading" => 'YaWF setup',
				"desc"    => 'This setup helps to fill basic configuration needed to run YaWF. Fill in the required information and press next step.',
			));

			$f->label('Basic site information');
			$f->input_text('name', 'Site name', true);
			$f->group_end();

			$f->label('Database information');

			$f->input(array(
				"name"     => 'database_ident',
				"label"    => l('Identificator'),
				"required" => true,
				"value"    => 'yawf',
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
					"MySQL"      => 'mysqli',
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
				"value"    => 'yawf',
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
				"type"     => 'checkbox',
				"info"     => l('The database driver will not connect until query is sent'),
			));

			$f->submit('Next step');

			if ($f->passed()) {
				$d = $f->get_data();
				$settings = array(
					"database" => array(
						"ident"    => $d['database_ident'],
						"driver"   => $d['database_driver'],
						"database" => $d['database_name'],
						"host"     => $d['database_host'],
						"username" => $d['database_user'],
						"password" => $d['database_pass'],
						"lazy"     => false,
					),
					"seo" => array(
						"title" => $d['name'],
					),
				);

				try {
					Database::connect($settings['database']);
				} catch (\DatabaseException $e) {}

				if (Database::is_connected()) {
					self::save($settings);
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

			cfgs(array('database', 'list', $data['ident'], 'charset'), 'utf8');
			cfgs(array('database', 'list', $data['ident'], 'is_yawf_home'), true);
			cfgs(array('database', 'default'), $data['database']['ident']);
			cfgs(array('database', 'connect'), array($data['database']['ident']));
			cfgs(array('default', 'title'), $data['seo']['title']);

			\System\Settings::save('default');
			\System\Settings::save('database');

			self::lock();
			self::finish();
		}


		/** Lock installer from running again
		 * @returns void
		 */
		protected static function lock()
		{
			if (!($action = @file_put_contents(ROOT.\System\Settings::DIR_CONF_ALL.'/install.lock', time()))) {
				throw new \InternalException(sprintf(
					l('Failed to lock installer. Please check permissions on your \'%s\' directory and re-run the installer.'),
					ROOT.\System\Settings::DIR_CONF_ALL
				));
			}
		}


		/** Print out "install complete" message and exit
		 * @returns void
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
							ROOT.\System\Package::PATH_BIN
						),
					)),
				)
			));
		}
	}
}
