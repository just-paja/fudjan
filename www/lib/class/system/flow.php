<?

namespace System
{
	abstract class Flow
	{

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


		public static function enqueue(Module &$module)
		{
			self::$queue[] = $module;
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
			reset(self::$queue);
			while(!empty(self::$queue)){
				$mod = array_shift(self::$queue);
				$retval = $mod->make();
				Status::log('Modules', array($mod->get_path() ), !!$retval, !!$retval);
				if($r = &self::$redirect[REDIRECT_AFTER_FLOW]) self::redirect_now($r);
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
			$r = array("url" => $url, "status" => any($opts['status']) ? $opts['status']:'', "when" => any($opts['when']) ? $opts['when']:REDIRECT_AFTER_FLOW);
			$opts['when'] == REDIRECT_IMMEDIATELY && self::redirect_now($r);
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
	}
}
