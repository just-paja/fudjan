<?

namespace System\Resource
{
	class Text extends \System\Resource\Generic
	{
		const NOT_FOUND = 'console.log("Jaffascript module not found: %s");';
		const MIME_TYPE = 'text/javascript';
		const POSTFIX_OUTPUT = '.txt';

		static protected $postfixes = array('txt', 'text');

		protected $base = array();


		public function resolve()
		{
			try {
				$list = \System\Settings::get('resources', 'packages', $this->type, $this->name);
			} catch(\System\Error\Config $e) {
				$list = null;
			}

			try {
				$this->minify = !\System\Settings::get('dev', 'debug', 'frontend');
			} catch (\System\Error\Config $e) {
				$this->minify = false;
			}

			$this->exists = !!$list;
			$this->base = $list;
		}


		public function read()
		{
			$this->content = $this->get_content($this->get_file_list($this->base));
			$this->parse();

			if ($this->minify) {
				$this->compress();
			}
		}


		public function parse()
		{
		}


		public function compress()
		{
		}


		public function set_response()
		{
			$this->response->set_content($this->content);
		}


		/** Get content of requested files and minify it
		 * @param array $info  Type info
		 * @param list  $files List of files
		 * @return string
		 */
		private function get_content(array $files)
		{
			$cache = $this->get_cache_path($files[self::KEY_SUM]);

			if (!$this->debug && file_exists($cache)) {
				$content = \System\File::read($f);
			} else {
				$content = '';

				try {
					$cache = \System\Settings::get('cache', 'resources');
				} catch (\System\Error $e) {
					$cache = false;
				}

				if (any($info[self::KEY_CALLBACK_PARSE])) {
					$content = call_user_func_array($info[self::KEY_CALLBACK_PARSE], array($info, $files));
				} else {
					$content = $this->get_content_from_files($files);
				}

				if (!$this->minify) {
					$list = "/* Used files\n\t".implode("\n\t", $files[self::KEY_FOUND])."\n*/\n\n";
					$content = $list.$content;
				}

				if ($cache && !$this->debug) {
					\System\File::put($this->get_cache_path($files[self::KEY_SUM]), $content);
				}
			}

			return $content;
		}


		public static function get_content_from_files(array $files)
		{
			$content = '';

			foreach ($files[self::KEY_MISSING] as $file) {
				$content .= sprintf($info[self::KEY_STRING_NOT_FOUND], $file);
			}

			foreach ($files[self::KEY_FOUND] as $file) {
				$content .= self::replace_tags(file_get_contents($file));
			}

			return $content;
		}


		public static function replace_tags($content)
		{
			return preg_replace_callback('/<([A-Za-z_]+)\(([0-9A-Za-z\/\.\,\-]+)\)(\w\?|\w?([^>]+)+)?>/', array('\System\Resource\Text', 'resolve_tag'), $content);
		}


		public static function resolve_tag($matches)
		{
			$tag = $matches[1];
			$args = explode(',', $matches[2]);
			$result = self::tag($tag, $args);

			if (any($matches[3])) {
				$pipes = preg_replace('/^[^a-zA-Z]+/', '', trim($matches[3]));
				$pipes = explode('|', $pipes);

				foreach ($pipes as $pipe) {
					if (!is_array($result)) {
						$result = array($result);
					}

					$result = self::tag(trim($pipe), $result);
				}
			}

			return $result;
		}


		public static function tag($name, array $args)
		{
			$tags = \System\Settings::get('resources', 'tags');
			$result = null;
			$matched = false;

			foreach ($tags as $cfg_tag) {
				if ($cfg_tag['name'] == $name) {
					$result = call_user_func_array($cfg_tag['callback'], $args);
					$matched = true;
				}
			}

			if ($matched) {
				return $result;
			}

			throw new \System\Error\Config("Tag not found", $name);
		}



		public static function tag_cfg()
		{
			return \System\Settings::get(func_get_args());
		}


		public static function tag_resource($type, $name)
		{
			return self::get_url('static', $type, $name);
		}


		public static function tag_icon($name, $size)
		{
			$name = explode('/', $name);
			$cat = array_shift($name);
			array_unshift($name, $size);
			array_unshift($name, $cat);

			return self::tag_resource(self::TYPE_ICONS, implode('/', $name), '.png');
		}


		public static function tag_pixmap($name)
		{
			$name = explode('.', $name);
			$suffix = array_pop($name);
			$name = implode('.', $name);

			return self::tag_resource(self::TYPE_PIXMAPS, $name, $suffix);
		}


		/** Get list of available files from user input
		 * @param string $type
		 * @param list   $modules
		 * @return array
		 */
		public function get_file_list($modules)
		{
			$found = array();
			$missing = array();

			try {
				$use_cache = \System\Settings::get('cache', 'resources');
			} catch(\System\Error\Config $e) {
				$use_cache = false;
			}

			if ($use_cache) {
				$dirs = array(BASE_DIR.\System\Cache::DIR_CACHE);
			} else {
				if ($this->src == 'static') {
					$src = self::DIR_STATIC;
				} else {
					$src = self::DIR_MEDIA;
				}

				$dirs = \System\Composer::list_dirs($src);
			}

			foreach ($modules as $module) {
				$mod_found = false;

				foreach ($dirs as $dir) {

					foreach ($this::$postfixes as $postfix) {
						$path = $dir.DIRECTORY_SEPARATOR.$module;

						if (file_exists($p = $path.'.list')) {
							$list = $this->get_file_list(array_map('trim', array_filter(explode("\n", \System\File::read($p)))));
							$found = array_merge($found, $list[self::KEY_FOUND]);
							$missing = array_merge($missing, $list[self::KEY_MISSING]);
							$mod_found = true;
							break;
						} else if (is_file($p = $path) || is_file($p = $path.'.'.$postfix)) {
							$found[] = $p;
							$mod_found = true;
						}

						if (is_dir($path)) {
							$json = null;
							$meta = self::get_meta($path, 'bower.json');

							if (!$meta) {
								$meta = self::get_meta($path, 'package.json');
							}

							if ($meta) {
								$files = array();

								foreach ($meta as $file) {
									$files[] = str_replace($dir.'/', '', $path.'/'.$file);
								}

								$list = $this->get_file_list($files);
								$found = array_merge($found, $list[self::KEY_FOUND]);
								$mod_found = true;
								break;
							} else {
								$files = \System\Directory::find_all_files($path);

								foreach ($files as $key=>$tmp_file) {
									$files[$key] = str_replace($dir.'/', '', $files[$key]);
								}

								$list = $this->get_file_list($files);
								$found = array_merge($found, $list[self::KEY_FOUND]);
								$missing = array_merge($missing, $list[self::KEY_MISSING]);
								$mod_found = true;
								break;
							}
						}
					}

					if ($mod_found) {
						break;
					}
				}

				if (!$mod_found) {
					$missing[] = $module;
				}
			}

			return array(
				self::KEY_FOUND   => array_unique(array_filter($found)),
				self::KEY_MISSING => array_unique(array_filter($missing)),
				self::KEY_SUM     => self::get_module_sum_from_list($modules),
			);
		}


		public static function get_meta($path, $file)
		{
			$json = null;
			$meta = null;

			if (file_exists($p = $path.'/'.$file)) {
				$json = \System\Json::read($p);
			}

			if ($json) {
				if (isset($json['include'])) {
					$meta = $json['include'];
				} else if (isset($json['main'])) {
					$meta = $json['main'];

					if (!is_array($meta)) {
						$meta = array($meta);
					}
				}
			}

			return $meta;
		}



		public static function filter_output_content($type, &$content)
		{
			if (is_array($content)) {
				if (any($content)) {
					self::resource_list_save($type, $content);
					$name = self::get_resource_list_name($content);
					$content = self::get_resource_list_wget_name($type, $name);
				} else {
					$content = null;
				}
			}

			return $content;
		}


		private static function resource_list_save($type, &$content)
		{
			$content = array_unique($content);
			$name = self::get_resource_list_name($content);
			$file = self::get_resource_list_path($type, $name);

			if (!file_exists($file)) {
				\System\File::put($file, implode(NL, $content));
			}
		}


		public static function get_resource_list_name(array $content)
		{
			return md5(implode(':', $content));
		}


		public function get_resource_list_path()
		{
			return BASE_DIR.self::DIR_CACHE.DIRECTORY_SEPARATOR.$this->name.'.list';
		}



		/** Get md5 sum of module
		 * @param string $str
		 * @return string
		 */
		private static function get_module_sum($str)
		{
			return md5($str);
		}




		/** Get md5 sum of modules
		 * @param list $modules
		 * @return string
		 */
		private static function get_module_sum_from_list(array $modules)
		{
			return self::get_module_sum(implode(':', $modules));
		}


		/**
		 * Get name of file to cache content in
		 *
		 * @param string $sum
		 * @return string
		 */
		public function get_cache_name($sum)
		{
			return $sum.'.'.self::get_serial().(self::POSTFIX_OUTPUT);
		}


		/**
		 * Get path of caching file
		 *
		 * @param string $sum
		 * @return string
		 */
		public function get_cache_path($sum)
		{
			return BASE_DIR.self::DIR_CACHE.DIRECTORY_SEPARATOR.self::get_cache_name($sum);
		}
	}
}
