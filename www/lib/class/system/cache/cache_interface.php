<?

namespace Core\System\Cache;

interface CacheInterface
{

	public static function setup($ttl, $storage = null, $port = null);
	public static function fetch($path, &$var);
	public static function get($path);
	public static function set($path, $value);
	public static function release($path);
	public static function flush();

	public static function set_ttl($ttl);
	public static function get_ttl();

	public static function set_storage($host, $port = null);
	public static function get_storage();

}
