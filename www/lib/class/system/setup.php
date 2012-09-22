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

			$f->submit('Next step');
			
			if ($f->passed()) {
				$d = $f->get_data();

				try {
					Database::connect(array(
						"driver"   => $d['database_driver'],
						"database" => $d['database_name'],
						"host"     => $d['database_host'],
						"username" => $d['database_user'],
						"password" => $d['database_pass'],
						"lazy"     => false,
					));
				} catch (\DatabaseException $e) {}
				
				if (Database::is_connected()) {
					self::save($d);
				} else {
					$f->group_error(array('database_name'), 'Could not connect to database');
					$f->out();
				}
			} else {
				$f->out();
			}
		}
		
		
		private static function save(array $data)
		{
			var_dump($data);
		}
	}
}
