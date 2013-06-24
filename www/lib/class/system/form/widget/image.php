<?

namespace System\Form\Widget
{
	class Image extends \System\Form\Widget
	{
		const KIND  = 'input';
		const TYPE  = 'image';
		const IDENT = 'image';
		const MODEL = '\System\Image';

		protected static $attrs = array(
			"thumb_size" => array("varchar"),
		);

		protected static $inputs = array(
			array(
				"ident"    => 'action',
				"name"     => '%s_action',
				"type"     => 'action',
				"label"    => 'form_input_image_action',
				"required" => true,
				"options"  => array(
					\System\Form\Widget\Action::NONE   => 'form_image_input_none',
					\System\Form\Widget\Action::KEEP   => 'form_image_input_keep',
					\System\Form\Widget\Action::UPLOAD => 'form_image_input_upload',
					\System\Form\Widget\Action::URL    => 'form_image_input_url',
				),
			),
			array(
				"ident"    => 'file',
				"name"     => '%s_file',
				"type"     => 'file',
				"label"    => 'form_input_image_file',
			),
			array(
				"ident"    => 'url',
				"name"     => '%s_url',
				"type"     => 'url',
				"label"    => 'form_input_image_url',
			),
		);

		protected static $resources = array(
			"scripts" => array('pwf/form/picker_image'),
			"styles"  => array('pwf/form/picker_image'),
		);



		public function render(\System\Template\Renderer $ren)
		{
			$this->use_resources($ren);

			$tools    = $this->get_tools();
			$inputs   = array();
			$value    = $this->form()->get_input_value_by_name($this->name);
			$label    = \System\Form\Renderer::label($this->form(), $this->label.':', 'label-widget');
			$content  = array();

			$actionkit = array_shift($tools);

			$content[] = div('actionkit', \System\Form\Renderer::render_element($ren, $actionkit));

			if ($value) {
				$content[] = div('image', $ren->link($value->get_path(), $value->to_html($ren), array("class" => 'lightbox')));
			}

			foreach ($tools as $tool) {
				$tool_html = \System\Form\Renderer::render_element($ren, $tool);

				if (!is_null($tool_html)) {
					$inputs[] = li($tool_html, \System\Form\Renderer::get_object_class($tool));
				}
			}

			$tools_html = ul('widget-tools', $inputs);
			$errors = \System\Form\Renderer::render_error_list($ren, $this);
			$content[] = $tools_html;

			return implode('', array(
				$label,
				div('input-content', $content),
				$errors,
				span('cleaner', '')
			));
		}



		public function is_valid()
		{
			$valid = true;
			$value = parent::assemble_value();

			if ($this->form()->submited()) {
				$action  = $this->tools['action']->assemble_value();
				$val_up  = $this->form()->input_value($this->tools['file']->name);
				$val_url = $this->form()->input_value($this->tools['url']->name);

				if ($action == \System\Form\Widget\Action::UPLOAD && !$val_up) {
					$this->form()->report_error($this->name, 'form_error_no_upload');
					$valid = false;
				}

				if ($action == \System\Form\Widget\Action::URL && !$val_url) {
					$this->form()->report_error($this->name, 'form_error_no_url');
					$valid = false;
				}
			}

			return $valid;
		}
	}
}
