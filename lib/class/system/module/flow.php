<?

/** System flow
 * @package system
 */
namespace System\Module
{
	/** Class that manages modules in form of queue.
	 * @used-by \System\Page
	 */
	class Flow
	{
		const REDIRECT_LATER         = 1;
		const REDIRECT_IMMEDIATELY   = 2;
		const REDIRECT_AFTER_MODULES = 3;
		const STOP = 4;

		/** Modules are enqueued here
		 * @param array
		 */
		private $queue = array();

		/** Redirects are enqueued here
		 * @param array
		 */
		private $redirect = array();


		/** Timer for system flow (start of exectution)
		 * @param float
		 */
		private $start_time = 0.0;


		/** Timer for system flow (end of exectution)
		 * @param float
		 */
		private $stop_time = 0.0;


		/** Response
		 * @param \System\Http\Response
		 */
		private $response;


		/** DBus
		 * @param \System\Module\DataBus
		 */
		private $dbus;


		/** Stopped
		 * @param bool
		 */
		private $stopped = false;


		/** Class init
		 * @return void
		 */
		public function __construct(\System\Http\Response $response, array $modules = array())
		{
			$this->response = $response;
			$this->dbus = new \System\Module\DataBus($this);

			foreach ($modules as $name=>$mod) {
				if ($mod instanceof \System\Module) {
					$this->enqueue($mod);
				} else {
					$locals = isset($mod[1]) ? $mod[1]:array();

					if (!is_numeric($name)) {
						$locals['module_id'] = $name;
					}

					$this->add($mod[0], $locals, isset($mod[2]) ? $mod[2]:array());
				}
			}
		}


		/** Add module into queue
		 * @param string $module  Module path
		 * @param array $locals  Local variables
		 * @param array $parents List of ids of parent modules - can inherit data by DataBus
		 * @uses \System\DataBus
		 * @return void
		 */
		public function add($module, array $locals = array(), array $parents = array())
		{
			if (empty($locals['mod-conds']) || (is_array($locals['mod-conds']) && Module::eval_conds($locals['mod-conds']))) {
				$this->enqueue(new \System\Module($module, $locals, $parents));
			}
		}


		/** Enqueue module instance into queue
		 * @param \System\Module &$module
		 * @return void
		 */
		public function enqueue(\System\Module $module)
		{
			$this->queue[] = $module->bind_to_flow($this);
		}


		/** Public getter of response
		 * @return \System\Http\Response
		 */
		public function response()
		{
			return $this->response;
		}


		public function stop()
		{
			$this->stopped = true;
		}


		/** Run all modules in queue
		 * @return void
		 */
		public function exec()
		{
			$this->start_time = microtime(true);

			while (!empty($this->queue)) {
				$mod = array_shift($this->queue);

				if (!$this->stopped) {
					$retval = $mod->exec();

					if (any($this->redirect[self::REDIRECT_LATER])) {
						$r = &$this->redirect[self::REDIRECT_LATER];
						\System\Http\Response::redirect($r['url'], $r['code']);
					}
				}
			}

			$this->stop_time = microtime(true);

			if (any($this->redirect[self::REDIRECT_AFTER_MODULES])) {
				$r = &$this->redirect[self::REDIRECT_AFTER_MODULES];
				\System\Http\Response::redirect($r['url'], $r['code']);
			}
		}


		/** Enqueue redirect. Passing REDIRECT_NOW will exit pwf and redirect immediately. Otherwise according to queue.
		 * @param string $url  URL to redirect to
		 * @param int    $code HTTP Status code to send
		 * @param int    $when When to redirect, one of (\System\Module\Flow::REDIRECT_LATER,\System\Module\Flow::REDIRECT_AFTER_MODULES,\System\Module\Flow::REDIRECT_NOW)
		 * @return void
		 */
		public function redirect($url, $code=\System\Http\Response::FOUND, $when=self::REDIRECT_AFTER_MODULES)
		{
			$when === self::REDIRECT_IMMEDIATELY && \System\Http\Response::redirect($url, $code);
			$this->redirect[$when] = array("url" => $url, "code" => $code);
		}


		/** Get execution time of module queue
		 * @return float
		 */
		public function get_exec_time()
		{
			return $this->stop_time - $this->start_time;
		}


		/** Returns whole queue with module instances
		 * @return list
		 */
		public function get_queue()
		{
			return $this->queue;
		}


		/** Get dbus instance
		 * @return \System\Module\Dbus
		 */
		public function dbus()
		{
			return $this->dbus;
		}
	}
}
