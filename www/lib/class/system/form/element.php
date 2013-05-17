<?

namespace System\Form
{
	abstract class Element extends \System\Model\Attr
	{
		private $form;


		protected function construct()
		{
			if (isset($this->opts['form']) && is_object($this->opts['form']) && $this->opts['form'] instanceof \System\Form) {
				$f = $this->opts['form'];
				unset($this->opts['form']);
				$this->form($f);
			} else throw new \System\Error\Argument("You must pass instance of 'System\Form' into element attributes.");
		}


		public function form(\System\Form $f = null)
		{
			if (!is_null($f)) {
				$this->form = $f;
			}

			return $this->form;
		}
	}
}
