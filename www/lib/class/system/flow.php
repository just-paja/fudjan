<?

namespace System
{
	abstract class Flow
	{
		const REDIRECT_LATER         = 1;
		const REDIRECT_IMMEDIATELY   = 2;
		const REDIRECT_AFTER_MODULES = 3;

		private static $queue = array();
		private static $redirect = array();
		private static $start_time = 0.0;


		public static function enqueue(Module &$module)
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


		public static function add($module, array $locals = array(), array $parents = array())
		{
			if (empty($locals['mod-conds']) || (is_array($locals['mod-conds']) && Module::eval_conds($locals['mod-conds']))) {
				$mod = new Module($module, $locals, $parents);
				self::enqueue($mod);
			}
		}


		public static function run()
		{
			while (!empty(self::$queue)) {
				$mod = array_shift(self::$queue);
				$retval = $mod->make();

				if (any(self::$redirect[self::REDIRECT_LATER])) {
					self::redirect_now($r);
				}
			}

			if (any(self::$redirect[self::REDIRECT_AFTER_MODULES])) {

			}

			\System\Http::save_referer();
		}


		public static function redirect($url, $code, $when=self::REDIRECT_AFTER_MODULES)
		{
			$when === self::REDIRECT_IMMEDIATELY && \System\Http::redirect($url, $code);
			self::$redirect[$r['when']] = array("url" => $url, "code" => $code);
		}


		public static function run_messages()
		{
			if(is_array($msgs = Message::get_all())) {
				foreach($msgs as $msg){
					self::add('system/message', array("message" => $msg));
				}
				self::run();
			}
		}


		public static function get_exec_time()
		{
			return microtime(true) - self::$start_time;
		}


		public static function get_queue()
		{
			return self::$queue;
		}
	}
}
