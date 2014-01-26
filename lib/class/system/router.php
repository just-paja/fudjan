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


		/** Get request domain from request HTTP_HOST
		 * @param string $host HTTP_HOST|Domain name
		 * @return string|bool False on no match
		 */
		public static function get_domain($host)
		{
			$domains = cfg('domains');

			try {
				if (cfg('domains', $host)) {
					return $host;
				}
			} catch(\System\Error\Config $e) {
				foreach ($domains as $domain => $config) {
					if (isset($config['rules'])) {
						if (self::domain_match($host, $config)) {
							return $domain;
						}
					} else {
						throw new \System\Error\Format(sprintf("Domain '%s' must have key 'rules' and 'init' defined!", $domain));
					}
				}
			}

			return false;
		}


		/** Get definition of path
		 * @param string $host Domain to choose from
		 * @param string $path Path to check
		 * @param array  $args Place to put URL arguments
		 * @return array|bool False on failure
		 */
		public static function get_path($host, $path, array &$args = array())
		{
			if ($domain = self::get_domain($host)) {
				try {
					$routes = cfg('routes', $domain);

					foreach ($routes as $route) {
						if (isset($route[0])) {
							$route_urls = is_array($route[0]) ? $route[0]:array($route[0]);

							foreach ($route_urls as $route_url) {
								$matches = array();

								if (self::json_preg_match($route_url, $path, $args)) {
									return $route;
								}
							}
						}
					}
				} catch (\System\Error $e) {
					throw cfg('dev', 'debug') ? new \System\Error\Config(sprintf("There are no routes for domain '%s'.", $domain), sprintf("Create file '%s.json' in '%s' and make some routes.", $domain, \System\Settings::DIR_CONF_ROUTES)):new \System\Error\NotFound();
				}
			} else {
				throw cfg('dev', 'debug') ? new \System\Error\Config(sprintf("Domain '%s' was not found in domain config.", $host), sprintf("Add it to your global config in '%s/domains.json'.", \System\Settings::DIR_CONF_GLOBAL)):new \System\Error\NotFound();
			}

			return false;
		}


		public static function get_url($host, $name, array $args = array(), $variation = 0)
		{
			if ($domain = self::get_domain($host)) {
				try {
					$routes = cfg('routes', $domain);
				} catch (\System\Error\Config $e) {
					$routes = array();
				}

				foreach ($routes as $route) {
					if (isset($route[0]) && isset($route[2]) && $name == $route[2]) {
						if ($variation > 0) {
							if (is_array($route[0])) {
								$route_url = $route[0][$variation];
							} else throw new \System\Error\Argument(sprintf("Named route called '%s' does not have any variations.", $name));
						} else {
							$route_url = is_array($route[0]) ? $route[0][0]:$route[0];
						}

						$search = 'open';
						$route_args = array();
						$path = str_split($route_url, 1);

						for ($pos = 0; $pos < count($path); $pos++) {
							if ($search == 'open') {
								if ($path[$pos] == '(') {
									$arg = array($pos);
									$search = 'close';
								}
							}

							if ($search == 'close') {
								if ($path[$pos] == ')') {
									$arg[1] = $pos + 1;
									$route_args[] = $arg;
									$search = 'open';
								}
							}
						}

						if (($c = count($route_args)) <= count($args)) {
							$str = '';

							if ($c > 0) {
								$num = 0;

								foreach ($args as $num=>$arg) {
									$start = $num == 0 ? 0:$route_args[$num-1][1];

									for ($letter = $start; $letter < $route_args[$num][0]; $letter ++) {
										$str .= $path[$letter];
									}

									if (is_object($arg)) {
										if ($arg instanceof \System\Model\Database) {
											$val = $arg->get_seoname();
										} else throw new \System\Error\Argument(sprintf("Argument '%s' passed to reverse build route '%s' must be string or instance of System::Model::Database", $num, $name), sprintf("Instance of '%s' was given.", get_class($arg)));
									} else $val = $arg;

									$str .= $val;
								}

								$start = $route_args[$num][1];

								for ($letter = $start; $letter < count($path); $letter ++) {
									$str .= $path[$letter];
								}
							} else {
								$str = implode('', $path);
							}

							$str = str_replace(array('^', '$'), '', $str);
							return $str;
						} else {
							throw new \System\Error\Argument(sprintf("Named route called '%s' accepts %s arguments. %s were given.", $name, count($route_args), count($args)));
						}

						$result = $route_url;
						foreach ($args as $arg) {
							$result = preg_replace($route_url, $arg, $result);
						}

						return $result;
					}
				}

				throw new \System\Error\Argument(sprintf("Named route called '%s' was not found for domain '%s'", $name, $host));
			} else {
				throw new \System\Error\NotFound();
			}

			return false;
		}


		/** Match domain against allowed host list
		 * @param string $host
		 * @param array  $config
		 * @return bool
		 */
		private static function domain_match($host, array $config)
		{
			foreach ($config['rules'] as $rule) {
				if (self::json_preg_match($rule, $host)) {
					return true;
				}
			}

			return false;
		}


		/** Short method for URL preg matching
		 * @param string $regexp  Regexp
		 * @param string $subject Tested string
		 * @param array  $matches Place to put matches
		 * @return bool
		 */
		public static function json_preg_match($regexp, $subject, array &$matches = array())
		{
			$matches_temp = array();
			$result = preg_match('/'.str_replace('/', '\\/', $regexp).'/', $subject, $matches_temp);

			if ($result) {
				foreach ($matches_temp as $key=>$match) {
					if ($key > 0) {
						$matches[] = $match;
					}
				}
			}

			return $result;
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
			if (!\System\File::check($p = BASE_DIR.self::REWRITE_TARGET)) {
				$val = \System\File::put($p, self::generate_rewrite_rules());
				Status::report('info', "Rewrite rules refreshed");
				return $val;
			}

			return true;
		}


		/** Get all named routes available
		 * @return array
		 */
		public static function get_named_routes()
		{
			$all_routes = cfg('routes');
			$path_list = array();

			foreach ($all_routes as $domain=>$routes) {
				$path_list[$domain] = array();

				foreach ($routes as $route) {
					if (isset($route[0]) && isset($route[2])) {
						$path_list[$domain][$route[2]] = $route[0];
					}
				}
			}

			return $path_list;
		}
	}
}
