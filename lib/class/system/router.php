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
		const MATCH_ATTR = '/\{([a-z_]+:[a-z_]+(:[a-z]+(:[^:]+)?)?)\}/';


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
		public static function get_path($domain, $path, array &$args = array(), array &$params = array())
		{
			$r = null;

			try {
				$routes = cfg('routes', $domain);
			} catch (\System\Error\Config $e) {
				$routes = array();
				throw \System\Settings::get('dev', 'debug', 'backend') ?
					new \System\Error\Config(sprintf("There are no routes for domain '%s'.", $domain), sprintf("Create file '%s.json' in '%s' and make some routes.", $domain, \System\Settings::DIR_CONF_ROUTES)):
					new \System\Error\NotFound();
			}

			foreach ($routes as $route) {
				if (!isset($route['url'])) {
					throw new \System\Error\Config('Index url must be defined for route', $route);
				}

				if (self::match($route['url'], $path, $args, $params)) {
					$r = $route;
					break;
				}
			}

			return $r;
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


		/**
		 * Is this string usable as domain?
		 *
		 * @param string $host
		 * @return bool
		 */
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
		 * Get named route from host or domain
		 *
		 * @param string $host
		 * @param string $name
		 * @return null|array
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
		 * Get route with simplified arguments
		 *
		 * @param string $host
		 * @param string $name
		 * @return string
		 */
		public static function get_route_str($host, $name)
		{
			$route = self::get_route($host, $name);

			if (!$route) {
				throw new \System\Error\Config('Route was not found', $name);
			}

			return self::get_pattern_simplified($route['url']);
		}


		/**
		 * Get simplified pattern
		 *
		 * @param string $pattern
		 * @return string
		 */
		public static function get_pattern_simplified($pattern)
		{
			return preg_replace_callback(self::MATCH_ATTR, function($matches) {
				$attr = explode(':', $matches[1]);
				return '{' . $attr[0] . '}';
			}, $pattern);
		}


		/**
		 * Get list of pattern attributes
		 *
		 * @param string $pattern
		 * @return array
		 */
		public static function get_pattern_attrs($pattern)
		{
			$test  = self::MATCH_ATTR;
			$list  = array();
			$match = array();
			$res   = array();

			preg_match_all($test, $pattern, $match);
			array_shift($match);

			if (isset($match[0])) {
				$res = $match[0];
			}

			foreach ($res as $m) {
				if ($m) {
					$attr = explode(':', $m);
					$list[] = array(
						"name"     => $attr[0],
						"type"     => isset($attr[1]) ? $attr[1]:'any',
						"required" => isset($attr[2]) ? $attr[2] == 'yes':true,
						"choices"  => isset($attr[3]) ? explode(',', $attr[3]):null
					);
				}
			}

			return $list;
		}


		public static function get_pattern_test($pat)
		{
			$attrs = self::get_pattern_attrs($pat);

			foreach ($attrs as $attr) {
				$cname = '\System\Router\Arg\\'.\System\Loader::get_class_from_model($attr['type']);
				$test  = '/\{' . $attr['name'] . ':' . $attr['type'];

				if ($attr['required']) {
					$test .= '(:yes)?';
				} else {
					$test .= ':no';
				}

				if (isset($attr['choices'])) {
					$test .= ':'.implode(',', $attr['choices']);
					$sub = '('. implode('|', $attr['choices']) .')';
				} else {
					$sub = '(' . $cname::PATTERN . ')' . ($attr['required'] ? '':'?');
				}

				$test .=  '\}/';

				$pat = preg_replace($test, $sub, $pat);
			}

			return $pat;
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
				$attrs = self::get_pattern_attrs($route['url']);
				$c = count($attrs);

				if ($c != count($args)) {
					throw new \System\Error\Argument(sprintf("Named route called '%s' accepts %s arguments. %s were given.", $name, count($attrs), count($args)));
				}

				$str = self::get_pattern_simplified($route['url']);

				if ($c > 0) {
					$num = 0;

					foreach ($attrs as $num=>$attr) {
						$val = null;

						if (isset($args[$attr['name']])) {
							$val = $args[$attr['name']];
						} else if (isset($args[$num])) {
							$val = $args[$num];
						}

						if (is_object($val)) {
							if ($val instanceof \System\Model\Database) {
								$val = $val->get_seoname();
							} else throw new \System\Error\Argument(sprintf("Argument '%s' passed to reverse build route '%s' must be string or instance of System::Model::Database", $num, $name), sprintf("Instance of '%s' was given.", get_class($arg)));
						}

						if (is_null($val) && $attr['required']) {
							throw new \System\Error\Argument('Argument must be supplied to build route', $name, $attr);
						}

						$str = str_replace('{'.$attr['name'].'}', $val, $str);
					}
				}

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


		public static function match($url, $path, array &$args = array(), array &$params = array())
		{
			$pattern = "^".self::get_pattern_test($url)."$";
			$match   = self::json_preg_match($pattern, $path, $args);

			if ($match) {
				$attrs = self::get_pattern_attrs($url);

				foreach ($attrs as $key=>$attr) {
					if (isset($args[$key])) {
						$params[$attr['name']] = $args[$key];
					} else {
						if ($attr['required']) {
							$match = false;
							break;
						}
					}
				}
			}

			return !!$match;
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
