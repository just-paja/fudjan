<?

/* TODO
 * Write own database layer and trash dibi
 */
namespace System
{
	class Database
	{
		public static function init()
		{
			$cfg = cfg('database');
			if (any($cfg['database'])) {
				\Dibi::connect(cfg('database'));
			} else throw new \ConfigException(l('No database is set. Please run `bin/db --setup` to set up basic config or create config files manually'));
		}
	}
}
