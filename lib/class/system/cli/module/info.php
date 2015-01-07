<?

namespace System\Cli\Module
{
	class Info extends \System\Cli\Module
	{
		protected static $info = array(
			'name' => 'cache',
			'head' => array(
				'Get information about your system',
			),
		);


		protected static $attrs = array(
			"help"    => array('bool', "value" => false, "short" => 'h', "desc"  => 'Show this help'),
		);


		protected static $commands = array(
			"libs"     => array('List used composer libs'),
			"config"   => array('List used config files'),
			"database" => array('Show detailed information about used databases'),
		);


		public static function cmd_libs()
		{
			\System\Init::basic();

			\System\Cli::out("Installed composer libraries");
			\System\Cli::sep();

			\System\Cli::out_flist(array(
				"list" => \System\Composer::get_libs(),
				"margin" => 2,
				"show_keys" => false
			));
		}


		public static function cmd_config()
		{
			\System\Init::full();

			\System\Cli::out_flist(array(
				"list" => array(
					"Framework"   => \System\Status::introduce(),
					"Environment" => \System\Settings::get_env()
				)
			));

			\System\Cli::out();
			\System\Cli::out("Loaded config files");
			\System\Cli::sep();

			\System\Cli::out_flist(array(
				"list" => \System\Settings::get_loaded_files(),
				"margin" => 2,
				"show_keys" => false
			));
		}


		public static function cmd_database()
		{
			\System\Init::full();

			$db_list = \System\Settings::get('database', 'list');

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

				\System\Cli::out('Database '.$db_ident);
				\System\Cli::sep();

				\System\Cli::out_flist(array(
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
		}
	}
}
