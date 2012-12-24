<?

namespace System\Form
{
	abstract class Element extends \System\Model\Attr
	{
		protected $form;


		/** Let element use this form
		 * @return void
		 */
		public function use_form(\System\Form &$f)
		{
			$this->form = &$f;
		}


		public function get_form()
		{
			return $this->form;
		}
	}
}
