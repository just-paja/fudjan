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
			$f->inputs_end();

			$f->label('Database information');
			$f->select('database_driver', array(
				"label" => 'Host name',
				"required" => true,
				"options"  => array(
					"MySQL"      => 'mysql',
					"PostgreSQL" => 'postgre',
				)
			));

			$f->input_text('database_host', 'Host name', true);
			$f->input_text('database_name', 'Database name', true);
			$f->input_text('database_user', 'User name', true);
			$f->input_text('database_pass', 'Password', true);
			$f->submit('Next step');
			$f->out();
		}
	}
}
