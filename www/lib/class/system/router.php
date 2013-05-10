<?

/** Rewrite handling
 * @package system
 * @subpackage routes
 */
namespace System
{
	/** Rewrite handling
	 * @package system
	 * @subpackage routes
	 */
	abstract class Router
	{
		const DIR_REWRITE = '/etc/rewrite.d';
		const REWRITE_TARGET = '/.htaccess';


		public static function get_domain($host)
		{
			$domains = cfg('domains');

			foreach ($domains as $domain => $config) {
				if (isset($config['rules']) and isset($config['init'])) {
					if (self::domain_match($host, $config)) {
						return $domain;
					}
				} else {
					throw new \System\Error\Format(sprintf("Domain '%s' must have key 'rules' and 'init' defined!", $domain));
				}
			}

			return false;
		}


		public static function get_path($host, $path)
		{
			if ($domain = self::get_domain($host)) {
				try {
					$routes = cfg('routes', $domain);

					foreach ($routes as $route) {
						if (isset($route[0])) {
							$route_urls = is_array($route[0]) ? $route[0]:array($route[0]);

							foreach ($route_urls as $route_url) {
								if (self::json_preg_match($route_url, $path)) {
									return $route;
								}
							}
						}
					}
				} catch (\System\Error $e) {
					throw cfg('dev', 'debug') ? new \System\Error\Config(sprintf("There are no routes for domain '%s'.", $domain), sprintf("Create file '%s.json' in '%s' and make some routes.", $domain, \System\Settings::DIR_CONF_ROUTES)):new \System\Error\NotFound();
				}
			} else {
				throw cfg('dev', 'debug') ? new \System\Error\Config(sprintf("Domain '%s' was not found in domain config.", $domain), sprintf("Add it to your global config in '%s/domains.json'.", \System\Settings::DIR_CONF_GLOBAL)):new \System\Error\NotFound();
			}

			return false;
		}


		public static function get_url($path)
		{

		}


		private static function domain_match($host, $config)
		{
			foreach ($config['rules'] as $rule) {
				if (self::json_preg_match($rule, $host)) {
					return true;
				}
			}

			return false;
		}


		private static function json_preg_match($regexp, $subject)
		{
			return preg_match('/'.str_replace('/', '\\/', $regexp).'/', $subject);
		}


		/** Generate rules for mod rewrite htaccess
		 * @return string
		 */
		public static function generate_rewrite_rules()
		{
			$dir = ROOT.self::DIR_REWRITE;
			$od = opendir($dir);
			$files = array();

			while ($file = readdir($od)) {
				if (strpos($file, '.') !== 0) {
					$files[$file] = \System\File::read($dir.'/'.$file);
				}
			}

			ksort($files);
			return implode("\n", $files);
		}


		/** Update htaccess rules
		 * @return bool
		 */
		public static function update_rewrite()
		{
			return \System\File::put(ROOT.self::REWRITE_TARGET, self::generate_rewrite_rules());
		}


	}
}
