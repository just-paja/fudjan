<?

namespace System\Form
{
	abstract class Element extends \System\Model\Attr
	{
		private $form;


		public function __construct(array $dataray = array())
		{
			if (isset($dataray['form']) && is_object($dataray['form']) && $dataray['form'] instanceof \System\Form) {
				$this->form = $dataray['form'];
				unset($dataray['form']);
			} else throw new \System\Error\Argument("You must pass instance of 'System\Form' into element attributes.");

			parent::__construct($dataray);
		}


		public function form(\System\Form $f = null)
		{
			if (!is_null($f)) {
				$this->form = $f;
			}

			return $this->form;
		}


		public function is_valid()
		{
			return true;
		}
	}
}
