<?

namespace System\Form
{
	class Helper
	{
		public static function error($msg)
		{
			return '<span class="form-error">'.$msg.'</span>';
		}
		
		
		private static function is_label_on_right($el)
		{
			return $el->type == 'checkbox' && empty($el->options);
		}


		public static function render_input(\System\Form\Input $el)
		{
			$el->content = $el->is_value_content() ? $el->value:$el->content;
			$label_on_right = self::is_label_on_right($el);

			$data = $el->get_data();
			$data['output'] = false;
			$data['close']  = true;
			$data['name']   = $el->get_form()->get_prefix().$data['name'];

			if ($el->kind == 'select') {
				$opts = array();

				foreach ($el->options as $label=>$opt) {
					$opts[] = \Tag::option(array(
						"content"  => $label,
						"value"    => $opt,
						"close"    => true,
						"output"   => false,
						"selected" => $el->value == $opt,
					));
				}

				$data['content'] = implode('', $opts);
			}

			$label = $el->has_label() ? \Tag::label(array(
				"class"   => array('input-label', 'input-label-'.($label_on_right ? 'right':'left')),
				"content" => $el->label.($label_on_right ? '':':'),
				"for"     => $el->id,
				"output"  => false,
			)):'';

			$html_element = $el->kind;
			$input = \Tag::div(array("content" => \Tag::$html_element($data), "class" => array('input-container'), "output" => false));
			$errors = '';
			$error_list = $el->get_form()->get_errors($el->name);

			if (!empty($error_list)) {
				$error_list_attrs = array(
					"content" => array(),
					"class"   => 'errors',
					"output"  => false,
				);
				
				foreach ($error_list as $e) {
					$error_list_attrs['content'][] = \Tag::li(array("content" => $e, "output"  => false));
				}
				
				$errors = \Tag::ul($error_list_attrs);
			}

			$label_and_input = $label_on_right ? $input.$label:$label.$input;
			echo $label_and_input.$errors;
		}


		public static function render_label(\System\Form\Label $el)
		{
			\Tag::label($el->get_data());
		}


		public static function render_element(\System\Form\Element $el)
		{
			switch (get_class($el)) {
				case 'System\Form\Container':
				{
					switch ($el->type) {
						case 'inputs':
							\Tag::fieldset();
								\Tag::ul($el->get_data());
									foreach ($el->get_elements() as $name=>$object) {
										\Tag::li(array("class" => self::get_object_class($object)));
										self::render_element($object);
										\Tag::close('li');
									}
								\Tag::close('ul');
							\Tag::close('fieldset');
							break;
					}
					break;
				}
				case 'System\Form\Input':
				{
					self::render_input($el);
					break;
				}
				case 'System\Form\Label':
				{
					self::render_label($el);
					break;
				}
			}
		}
		
		
		public static function get_object_class(\System\Form\Element $el)
		{
			$base_class = 'element';
			$class = array();
			
			if ($el instanceof \System\Form\Input) {
				$base_class = $el->kind;
				$class[] = 'input-'.$el->id;
				$class[] = 'input-'.(self::is_label_on_right($el) ? 'left':'right');
			} elseif ($el instanceof \System\Form\Label) {
				$base_class = 'label';
			}

			$class[] = 'form-'.$base_class;
			return $class;
		}
	}
}

