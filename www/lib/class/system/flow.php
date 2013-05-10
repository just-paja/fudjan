<?

/** System flow
 * @package system
 */
namespace System
{
	/** Class that manages modules in form of queue. Modules are inserted into flow by Page class
	 * @used-by \System\Page
	 */
	abstract class Flow
	{
		const REDIRECT_LATER         = 1;
		const REDIRECT_IMMEDIATELY   = 2;
		const REDIRECT_AFTER_MODULES = 3;

		/** Modules are enqueued here
		 * @param array
		 */
		private static $queue = array();

		/** Redirects are enqueued here
		 * @param array
		 */
		private static $redirect = array();


		/** Timer for system flow
		 * @param float
		 */
		private static $start_time = 0.0;


		/** Enqueue module instance into queue
		 * @param \System\Module &$module
		 * @return void
		 */
		public static function enqueue(\System\Module &$module)
		{
			self::$queue[] = $module;
		}


		/** Class init
		 * @return void
		 */
		public static function init()
		{
			self::$start_time = microtime(true);
		}


		/** Add module into queue
		 * @param string $module  Module path
		 * @param array $locals  Local variables
		 * @param array $parents List of ids of parent modules - can inherit data by DataBus
		 * @uses \System\DataBus
		 * @return void
		 */
		public static function add($module, array $locals = array(), array $parents = array())
		{
			if (empty($locals['mod-conds']) || (is_array($locals['mod-conds']) && Module::eval_conds($locals['mod-conds']))) {
				$mod = new Module($module, $locals, $parents);
				self::enqueue($mod);
			}
		}


		/** Run all modules in queue
		 * @return void
		 */
		public static function run(\System\Http\Response $response)
		{
			while (!empty(self::$queue)) {
				$mod = array_shift(self::$queue);
				$retval = $mod->make($response);

				if (any(self::$redirect[self::REDIRECT_LATER])) {
					$r = &self::$redirect[self::REDIRECT_LATER];
					\System\Http::redirect($r['url'], $r['code']);
				}
			}

			if (any(self::$redirect[self::REDIRECT_AFTER_MODULES])) {
				$r = &self::$redirect[self::REDIRECT_AFTER_MODULES];
				\System\Http::redirect($r['url'], $r['code']);
			}

			\System\Http::save_referer();
		}


		/** Enqueue redirect. Passing REDIRECT_NOW will exit pwf and redirect immediately. Otherwise according to queue.
		 * @param string $url  URL to redirect to
		 * @param int    $code HTTP Status code to send
		 * @param int    $when When to redirect, one of (\System\Flow::REDIRECT_LATER,\System\Flow::REDIRECT_AFTER_MODULES,\System\Flow::REDIRECT_NOW)
		 * @return void
		 */
		public static function redirect($url, $code=\System\Http::FOUND, $when=self::REDIRECT_AFTER_MODULES)
		{
			$when === self::REDIRECT_IMMEDIATELY && \System\Http::redirect($url, $code);
			self::$redirect[$when] = array("url" => $url, "code" => $code);
		}


		/** Deprecated method to pass all messages to user
		 * @deprecated
		 */
		public static function run_messages()
		{
			if(is_array($msgs = Message::get_all())) {
				foreach($msgs as $msg){
					self::add('system/message', array("message" => $msg));
				}
				self::run();
			}
		}


		/** Get execution time of module queue
		 * @return float
		 */
		public static function get_exec_time()
		{
			return microtime(true) - self::$start_time;
		}


		/** Returns whole queue with module instances
		 * @return list
		 */
		public static function get_queue()
		{
			return self::$queue;
		}
	}
}
