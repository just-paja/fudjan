<?

namespace Module\System
{
	class Layout extends \System\Module
	{
		public function run()
		{
			$this->req('slots');
			$this->partial('system/layout', $this->opts);
		}
	}
}
