<?

/** Modules
 * @package system
 */
namespace System
{
	/** System queue module class - represents controller
	 * @package system
	 * @property string $id      ID of module
	 * @property string $path    Module path eg. 'system/partial'
	 * @property array  $locals  Local data for module
	 * @property array  $parents List of parent IDs
	 */
	class Module extends \System\Model\Attr
	{
		const BASE_DIR = '/lib/module';

		/**
		 * Count of used module instances
		 *
		 * @param int
		 */
		static private $instance_count = 0;

		/**
		 * Local variables that are forced to be of array
		 * @param array
		 */
		static private $array_forced_locals = array("conds", "opts");

		static protected $attrs = array(
			"id"        => array("type" => 'varchar', 'is_null' => true),
			"path"      => array("type" => 'varchar'),
			"locals"    => array("type" => 'array'),
			"slot"      => array("type" => 'varchar', "is_null" => true, "default" => \System\Template::DEFAULT_SLOT),
			"parents"   => array("type" => 'array', "is_null" => true),
			"renderer"  => array("type" => 'object', "model" => '\System\Template\Renderer'),
			"request"   => array("type" => 'object', "model" => '\System\Http\Request'),
			"response"  => array("type" => 'object', "model" => '\System\Http\Response'),
			"flow"      => array("type" => 'object', "model" => '\System\Module\Flow'),
		);


		/**
		 * Public constructor compatible with attr model
		 * @return $this
		 */
		public function __construct(array $dataray)
		{
			$this::$instance_count ++;
			return parent::__construct($dataray);
		}


		/**
		 * Is this module accessible by current user?
		 *
		 * @return bool
		 */
		public function accessible()
		{
			return
				$this->request->user()->is_root() ||
				$this->request->user()->has_right('*') ||
				$this->request->user()->has_right(substr($this->get_path(), 1));
		}


		/**
		 * Get path to this module file location
		 *
		 * @return null|string
		 */
		public function get_file()
		{
			$path = explode('/', $this->path);
			$name = array_pop($path).'.php';
			$path = implode('/', $path);

			$dirs = \System\Composer::list_dirs(self::BASE_DIR.($path ? '/'.$path:''));

			foreach ($dirs as $dir) {
				if (file_exists($f = $dir.'/'.$name)) {
					return $f;
				}
			}

			return null;
		}


		/**
		 * Execute this module
		 *
		 * @return void
		 */
		public function exec()
		{
			$path = $this->get_file();

			if (!file_exists($path)) {
				throw new \System\Error\File(sprintf('Module not found: "%s", expected on path "%s".', $this->get_path(), $path));
			}

			if (!is_readable($path)) {
				throw new \System\Error\Permissions(sprintf('Cannot access module "%s". File is not readable.', $this->get_path()));
			}

			if (!$this->accessible()) {
				throw new \System\Error\Permissions(sprintf('Cannot access module "%s". Permission denied.', $this->get_path()));
			}

			$locals = $this->locals;

			def($locals['per_page'], 20);
			def($locals['page'], intval($this->request->get('page')));

			$locals['per_page'] = intval($locals['per_page']);
			$propagated = array();

			if (count($this->parents)) {
				$propagated = $this->dbus->get_data($this->parents);
				$locals = array_merge($locals, $propagated);
			}

			foreach (self::$array_forced_locals as $var) {
				if (isset($locals[$var]) && !is_array($locals[$var])) {
					throw new \System\Error\Argument(sprintf('Local variable "$%s" must be an array for module "%s"', $var, $this->get_path()));
				}
			}

			foreach ($locals as $key=>&$val) {
				if (is_string($val) && preg_match("/^\#\{[0-9]{1,3}\}$/", $val)) {
					$temp = $this->response->request->args;
					$temp_key = intval(substr($val, 2));

					if (isset($temp[$temp_key])) {
						$locals[$key] = $temp[$temp_key];
					} else throw new \System\Error\Argument(sprintf('Path variable #{%s} was not found.', $temp_key));
				}
			}

			$this->locals = $locals;
			extract($locals);

			$module   = $this;
			$response = $this->response;
			$renderer = $this->response->renderer;
			$request  = $this->response->request;
			$flow     = $this->response->flow;
			$locales  = $this->response->locales;

			$ren = &$renderer;
			$res = &$response;
			$rq  = &$request;

			$required = require($path);

			return !!$required;
		}


		/**
		 * Propagate this variable to child modules
		 *
		 * @param string $name
		 * @param mixed $data
		 * @return $this
		 */
		private function propagate($name, $data)
		{
			$this->dbus->add_data($this, $name, $data);
			return $this;
		}


		/**
		 * Require variable input. Throws exception if variable was not defined in local variable set.
		 *
		 * @param string $var_name
		 * @return mixed
		 */
		public function req($var_name)
		{
			if (!isset($this->locals[$var_name]) || is_null($this->locals[$var_name])) {
				throw new \System\Error\Argument(sprintf('Local variable "%s" must be defined and not null for module "%s"!', $var_name, $this->get_path()));
			}

			return $this->locals[$var_name];
		}


		/**
		 * Insert template into output queue
		 *
		 * @param string $name  Template name begining at /lib/template/partial
		 * @param array $locals Local variables for the template
		 * @return $this
		 */
		public function partial($name, array $locals = array())
		{
			$locals = array_merge($this->locals, $locals);
			$locals['module_id'] = $this->id;
			$this->response->renderer->partial($name, $locals, def($locals['slot'], Template::DEFAULT_SLOT));
			return $this;
		}


		/**
		 * Create new module id
		 *
		 * @return string
		 */
		static public function get_new_id()
		{
			return 'noname-'.self::$instance_count;
		}


		/**
		 * Get all available modules
		 *
		 * @param bool $with_perms
		 * @return array
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
					$mod['perms'] = \System\User\Perm::get_all()
						->where(array(
							"type" => 'module',
							"trigger" => $mod['path'],
						))
						->fetch();
				}
			}

			return $mods;
		}


		/**
		 * Check if module exists
		 *
		 * @param string $mod
		 */
		public static function exists($mod)
		{
			return file_exists(ROOT.self::BASE_DIR.'/'.$mod.'.php');
		}


		/**
		 * Evaluate conditions if the module can be used
		 *
		 * @param array $conds
		 * @return bool
		 */
		public static function eval_conds(array $conds)
		{
			$result = true;
			foreach ($conds as $cond_str) {
				strpos($cond_str, ',') === false && $cond_str .= ',';
				list($cond, $val) = explode(',', $cond_str, 2);
				switch ($cond) {
					case 'logged-in':
						$result = $result && $request->logged_in();
						break;
					case 'logged-out':
						$result = $result && !$request->logged_in();
						break;
				}
			}

			return empty($conds) || $result;
		}


		/**
		 * Stop executing flow and immediately return JSON response from this module data
		 *
		 * @param string $status
		 * @param string $message
		 * @param array  $data
		 * @param array  $meta
		 * @return $this
		 */
		private function json_response($status, $message=null, $data=null, $meta=null)
		{
			$response = array("status" => $status);

			!is_null($meta) && $response = array_merge($meta, $response);
			!is_null($message) && $response['message'] = $message;
			!is_null($data) && $response['data'] = $data;

			$this->response->format = 'json';
			$this->response->reset_renderer();
			$this->response->status($status);

			$this->partial('system/common', $response);
			$this->flow->stop();
			return $this;
		}
	}
}

