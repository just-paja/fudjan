<?

namespace System
{
	abstract class Flow
	{
		const REDIRECT_LATER  = 1;
		const REDIRECT_IMMEDIATELY = 2;

		private static $headers = array(
			200 => "HTTP/1.0 200 OK",
			301 => "HTTP/1.0 301 Moved Permanently",
			302 => "HTTP/1.0 302 Found",
			303 => "HTTP/1.0 303 See Other",
			307 => "HTTP/1.0 307 Temporary Redirect",
			403 => "HTTP/1.0 403 Forbidden",
			404 => "HTTP/1.0 404 Page Not Found",
		);

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
				Status::log('Modules', array($mod->get_path() ), !!$retval, !!$retval);
				if($r = &self::$redirect[self::REDIRECT_LATER]) self::redirect_now($r);
			}
			self::save_referer();
		}


		public static function redirect_now($r)
		{
			self::save_referer();
			session_write_close();
			header(self::$headers[$r['code'] ? $r['code']:302]);
			header("Location:".$r['url']);
			exit;
		}


		public static function redirect($url, array $opts = array())
		{
			$r = array("url" => $url, "status" => any($opts['status']) ? $opts['status']:'', "when" => any($opts['when']) ? $opts['when']:self::REDIRECT_LATER);
			$opts['when'] == self::REDIRECT_IMMEDIATELY && self::redirect_now($r);
			self::$redirect[$r['when']] = $r;
		}


		public static function run_messages()
		{
			if(is_array($msgs = Message::get_all())) {
				foreach($msgs as $msg){
					self::add('core/universal/message', array("message" => $msg));
				}
				self::run();
			}
		}


		private static function save_referer()
		{
			if(!!Status::$save_referer) $_SESSION['yacms-referer'] = $_SERVER['REQUEST_URI'];
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
