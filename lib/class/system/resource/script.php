<?

namespace System\Resource
{
	class Script extends \System\Resource\Text
	{
		const DIR_CACHE = '/var/cache/resources/scripts';
		const NOT_FOUND = 'console.log("Jaffascript module not found: %s");';
		const MIME_TYPE = 'text/javascript';
		const POSTFIX_OUTPUT = '.js';

		static protected $postfixes = array('js');


		public function compress()
		{
			$this->content = \System\Minifier\Scripts::minify($this->content);
		}
	}
}
