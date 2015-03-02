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
		/**
		 * Get request domain from request HTTP_HOST
		 *
		 * @param string $host HTTP_HOST|Domain name
		 * @return string|bool False on no match
		 */
		public static function get_domain($host)
		{
			try {
				$domains = \System\Settings::get('domains');
			} catch (\System\Error\Config $e) {
				$domains = array();
			}

			foreach ($domains as $domain => $config) {
				if (isset($config['rules'])) {
					if (self::domain_match($host, $config)) {
						return $domain;
					}
				} else {
					throw new \System\Error\Format(sprintf("Domain '%s' must have key 'rules' defined!", $domain));
				}
			}

			return false;
		}


		/**
		 * Get definition of path
		 *
		 * @param string $domain Domain to choose from
		 * @param string $path Path to check
		 * @param array  $args Place to put URL arguments
		 * @return array|bool False on failure
		 */
		public static function get_path($domain, $path, array &$args = array())
		{
			try {
				$routes = cfg('routes', $domain);
			} catch (\System\Error\Config $e) {
				$routes = array();
				throw \System\Settings::get('dev', 'debug', 'backend') ?
					new \System\Error\Config(sprintf("There are no routes for domain '%s'.", $domain), sprintf("Create file '%s.json' in '%s' and make some routes.", $domain, \System\Settings::DIR_CONF_ROUTES)):
					new \System\Error\NotFound();
			}

			foreach ($routes as $route) {
				if (isset($route['url'])) {
					$route_urls = is_array($route['url']) ? $route['url']:array($route['url']);

					foreach ($route_urls as $route_url) {
						if (self::json_preg_match($route_url, $path, $args)) {
							return $route;
						}
					}
				}
			}

			return false;
		}


		public static function get_first_url($path, array $args = array())
		{
			$domains = \System\Settings::get('domains');
			$url = false;

			foreach ($domains as $host => $cfg) {
				try {
					$url = self::get_url($host, $path, $args);
				} catch (\System\Error\Config $e) {
					continue;
				}

				if ($url) {
					break;
				}
			}

			return $url;
		}


		public static function is_domain($host)
		{
			try {
				cfg('domains', $host);
			} catch(\System\Error\Config $e) {
				return false;
			}

			return true;
		}


		/**
		 *
		 */
		public static function get_route($host, $name)
		{
			$route = null;

			if (self::is_domain($host)) {
				$domain = $host;
			} else {
				$domain = self::get_domain($host);
			}

			if ($domain) {
				try {
					$routes = cfg('routes', $domain);
				} catch (\System\Error\Config $e) {
					$routes = array();
				}

				foreach ($routes as $r) {
					if (isset($r['url']) && isset($r['name']) && $name == $r['name']) {
						$route = $r;
						break;
					}
				}
			}

			return $route;
		}


		/**
		 * Find named route and translate it with args
		 *
		 * @param string $host
		 * @param string $name
		 * @param array  $args
		 * @return string
		 */
		public static function get_url($host, $name, array $args = array())
		{
			$route = self::get_route($host, $name);

			if ($route) {
				$route_url = $route['url'];
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
					if (self::is_domain($host)) {
						$domain = $host;
					} else {
						$domain = self::get_domain($host);
					}

					$dns = \System\Settings::get('domains', $domain);

					if (!array_key_exists('htaccess', $dns) || $dns['htaccess']) {
						return $str;
					}

					return '?path=' . urlencode($str);
				} else {
					throw new \System\Error\Argument(sprintf("Named route called '%s' accepts %s arguments. %s were given.", $name, count($route_args), count($args)));
				}

				$result = $route_url;
				foreach ($args as $arg) {
					$result = preg_replace($route_url, $arg, $result);
				}

				return $result;

			} else {
				throw new \System\Error\Config(sprintf("Named route called '%s' was not found for domain '%s'", $name, $host));
			}

			return false;
		}


		/**
		 * Match domain against allowed host list
		 *
		 * @param string $host
		 * @param array  $config
		 * @return bool
		 */
		public static function domain_match($host, array $config)
		{
			foreach ($config['rules'] as $rule) {
				if (self::json_preg_match($rule, $host)) {
					return true;
				}
			}

			return false;
		}


		/**
		 * Short method for URL preg matching
		 *
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
					if (isset($route['url']) && isset($route['name'])) {
						$path_list[$domain][$route['name']] = $route['url'];
					}
				}
			}

			return $path_list;
		}


		/**
		 * Update htaccess rules
		 * @return bool
		 */
		public static function update_rewrite()
		{
			if (!\System\File::check($p = BASE_DIR.\System\Loader::FILE_REWRITE)) {
				$val = \System\File::put($p, \Helper\Htaccess::generate_rewrite_rules());
				Status::report('info', "Rewrite rules refreshed");
				return $val;
			}

			return true;
		}
	}
}
