<?

namespace System
{
	class Module
	{
		const BASE_DIR = '/lib/module';
		static private $instances;
		static private $array_forced_locals = array("conds", "opts");
		private $id, $path, $locals, $slot, $parents;


		public function __construct($module, $locals = array(), $parents = array())
		{
			self::$instances ++;
			$this->path = '/'.$module;
			$this->locals = $locals;
			$this->parents = $parents;

			if (!empty($this->locals['module_id'])) {
				$this->id = $this->locals['module_id'];
			} else {
				$this->id = self::get_new_id();
			}

			$this->slot = def($locals['slot'], Template::DEFAULT_SLOT);
		}


		/** Get local path to module
		 * @returns string
		 */
		public function get_path()
		{
			return $this->path;
		}


		/** Get module name
		 * @returns string
		 */
		public function get_name()
		{
			return t('module_name', $this->path);
		}


		/** Get module id unique on a page
		 * @returns string
		 */
		public function get_id()
		{
			return $this->id;
		}


		/** Run module
		 * @returns void
		 */
		public function make()
		{
			$path = ROOT.self::BASE_DIR.$this->path.'.php';

			if (file_exists($path)) {
				if (is_readable($path)) {
					if (user()->is_root() || user()->has_right_to('*') || user()->has_right_to(substr($this->get_path(), 1))) {
						if (!is_array($this->locals)) $this->locals = array($this->locals);
						$locals = &$this->locals;

						def($locals['per_page'], 20);
						def($locals['page'], intval(\System\Input::get('page')));

						if (is_array($locals)) {
							$input = Input::get('page');
							$propagated = array();

							if (any($this->parents)) {
								$propagated = DataBus::get_data($this->parents);
								$locals = array_merge($locals, $propagated);
							}

							foreach (self::$array_forced_locals as $var) {
								if (isset($locals[$var]) && !is_array($locals[$var])) {
									throw new \System\Error\Argument(sprintf('Local variable "$%s" must be an array for module "%s"', $var, $this->get_path()));
								}
							}

							foreach ($locals as $key=>&$val) {
								if (is_numeric($key)) {
									$key = 'local_attr_'.$key;
									$locals[$key] = &$val;
								} else {
									$key = str_replace('-', '_', $key);
								}

								$val === '#' && $val = end($input);
								if (!is_object($val) && !is_array($val) && preg_match("/^\#\{[0-9]{1,3}\}$/", $val)) {
									$temp = \System\Page::get_path_variables();
									$temp_key = intval(substr($val, 2));

									if (isset($temp[$temp_key])) {
										$val = $temp[$temp_key];
									} else throw new \System\Error\Argument(sprintf('Path variable #{%s} was not found.', $temp_key));
								}

								!is_object($val) && !is_array($val) && strpos($val, '#user{') === 0 && $val = soprintf(substr($val, 5), user());

								$$key = &$val;
							}
						}

						$req = require($path);

						if (any($propagate)) {
							DataBus::save_data($this, $propagate);
						}

						return !!$req;
					} else throw new \System\Error\Permissions(sprintf('Cannot access module "%s". Permission denied.', $this->get_path()));
				} else throw new \System\Error\Permissions(sprintf('Cannot access module "%s". File is not readable.', $this->get_path()));
			} else throw new \System\Error\File(sprintf('Module not found: "%s", expected on path "%s".', $this->get_path(), $path));
		}


		/** Require variable input. Throws exception if variable was not defined in local variable set.
		 * @param string $var_name
		 * @returns
		 */
		public function req($var_name)
		{
			if (!is_null($this->locals[$var_name])) {
				return $this->locals[$var_name];
			} else throw new \System\Error\Argument(sprintf('Local variable "%s" must be defined and not null for module "%s"!', $var_name, $this->get_path()));
		}


		/** Insert template into output queue
		 * @param string $name  Template name begining at /lib/template/partial
		 * @param array $locals Local variables for the template
		 * @returns void
		 */
		public function template($name, array $locals = array())
		{
			if ($name instanceof \System\Form)
			{
				$f = $name;
				$f->check_group_end();
				$f->check_tab_group_end();
				$f->check_inputs_end();
				$name = \System\Form::get_default_template();
				$locals += array("f" => $f);
			}

			$locals = array_merge($this->locals, $locals);
			$locals['module_id'] = $this->id;
			Template::insert($name, $locals, def($this->locals['slot'], Template::DEFAULT_SLOT));
		}


		/** Create new module id
		 */
		static public function get_new_id()
		{
			return 'noname-'.self::$instances;
		}


		/** Get all modules
		 * @param bool $with_perms
		 */
		public static function get_all($with_perms = false)
		{
			$mods = array();
			$path = ROOT.self::BASE_DIR;

			\System\Directory::find_all_files($path, $mods, '/\.php$/');
			sort($mods);

			foreach ($mods as &$mod) {
				$mod = array("path" => preg_replace('/\.php$/', '', substr($mod, strlen($path)+1)));

				if ($with_perms) {
					$mod['perms'] = get_all("\System\User\Perm", array(
						"type" => 'module',
						"trigger" => $mod['path'],
					))->fetch();
				}
			}

			return $mods;
		}


		/** Check if module exists
		 * @param string $mod
		 */
		public static function exists($mod)
		{
			return file_exists(ROOT.self::BASE_DIR.'/'.$mod.'.php');
		}


		/** Evaluate conditions if the module can be used
		 * @param array $conds
		 * @returns bool
		 */
		public static function eval_conds(array $conds)
		{
			$result = true;
			foreach ($conds as $cond_str) {
				strpos($cond_str, ',') === false && $cond_str .= ',';
				list($cond, $val) = explode(',', $cond_str, 2);
				switch ($cond) {
					case 'logged-in':
						$result = $result && \System\User::logged_in();
						break;
					case 'logged-out':
						$result = $result && !\System\User::logged_in();
						break;
				}
			}

			return empty($conds) || $result;
		}
	}
}

