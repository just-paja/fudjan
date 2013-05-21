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
			),
			array(
				"ident"    => 'file',
				"name"     => '%s_file',
				"type"     => 'file',
				"label"    => 'form_input_image_file',
				"required" => true,
			),
			//~ array(
				//~ "ident"    => 'url',
				//~ "name"     => '%s_url',
				//~ "type"     => 'url',
				//~ "label"    => 'form_input_image_url',
			//~ ),
		);

		protected static $resources = array();


		public function render(\System\Template\Renderer $ren)
		{
			$this->use_resources($ren);

			$tools    = $this->get_tools();
			$inputs   = array();
			$value    = $this->form()->get_input_value_by_name($this->name);
			$label    = \System\Form\Renderer::label($this->form(), $this->label);
			$content  = array();

			if ($value) {
				$content[] = div('image', $ren->link($value->get_path(), $value->to_html(), array("class" => 'lightbox')));
			}

			foreach ($tools as $tool) {
				$tool_html = \System\Form\Renderer::render_element($ren, $tool);

				if (!is_null($tool_html)) {
					$inputs[] = li($tool_html, \System\Form\Renderer::get_object_class($tool));
				}
			}

			$tools_html = ul('widget-tools', $inputs);
			$content[] = $tools_html;
			return div('input-container input-'.$this::IDENT, array($label, div('input-content', $content), span('cleaner', '')));
		}
	}
}
