<?

namespace System\Form
{
	abstract class Renderer
	{
		public static function render(\System\Template\Renderer $ren, \System\Form $form)
		{
			$output = array();
			$output[] = \Stag::fieldset(array(
				"class" => 'hidden',
				"content" => \Stag::input(array(
					"value"  => htmlspecialchars(json_encode($form->get_hidden_data())),
					"type"   => 'hidden',
					"name"   => $form->get_prefix().'data_hidden',
					"close"  => true,
				)),
			));

			$objects = $form->get_objects();

			foreach ($objects as $obj) {
				$output[] = self::render_element($ren, $obj);
			}

			$output[] = span('cleaner', '');

			$form_attrs = $form->get_attr_data();
			$form_attrs['content'] = $output;

			unset($form_attrs['class'], $form_attrs['id']);
			return div(array_merge(array('pwform'), (array) $form->class), \Stag::form($form_attrs), $form->id);
		}


		private function flush()
		{
			$this->output = array();
		}


		private static function render_element(\System\Template\Renderer $ren, \System\Form\Element $el)
		{
			switch (get_class($el)) {
				case 'System\Form\Container':
				{
					switch ($el->type) {
						case \System\Form\Container::TYPE_INPUTS:
						case \System\Form\Container::TYPE_BUTTONS:
							return self::render_container_inputs($ren, $el);
						case \System\Form\Container::TYPE_TAB: return self::render_container_tab($ren, $el);
						case \System\Form\Container::TYPE_TAB_GROUP: return self::render_container_tab_group($ren, $el);
					}
					break;
				}

				case 'System\Form\Input': return self::render_input($ren, $el);
				case 'System\Form\Label': return self::render_label($ren, $el);
				case 'System\Form\Text':  return self::render_text($ren, $el);

				default:
				{
					if ($el instanceof \System\Form\Widget) {
						return $el->render($ren);
					}
					break;
				}
			}
		}


		public static function render_label(\System\Form\Label $el)
		{
			return \Stag::label($el->get_data());
		}


		public static function label(\System\Form $form, $text, $for = null)
		{
			return self::render_label(new \System\Form\Label(array(
				"content" => $text,
				"form"    => $form,
				"for"     => $for,
			)));
		}


		private static function render_text(\System\Template\Renderer $ren, \System\Form\Text $el)
		{
			return
				self::render_label($ren, new Label(array("content" => $el->label))).
				div(array('input-container'), $el->content);
		}


		/** Render widget
		 * @param \System\Form\Widget $el Widget instance
		 * @return string
		 */
		public static function render_widget(\System\Template\Renderer $ren, \System\Form\Widget $el)
		{
			$el->use_resources($ren);

			$tools  = $el->get_tools();
			$inputs = array();

			foreach ($tools as $tool) {
				$inputs[] = li(self::render_element($ren, $tool));
			}

			$content = array(
				self::label($el->form(), $el->label),
				ul('widget-tools', $inputs),
			);
			return div('input-container input-'.$el::IDENT, $content);
		}



		public static function render_input(\System\Template\Renderer $ren, \System\Form\Input $el)
		{
			$el->content = $el->is_value_content() ? $el->value:$el->content;
			$label_on_right = self::is_label_on_right($el);

			$data = $el->get_data();
			$data['output'] = false;
			$data['close']  = true;
			$data['name']   = $el->form()->get_prefix().$data['name'];

			if ($el->kind == 'select') {
				$data['content'] = self::get_select_opts_html($el);
				unset($data['options']);
			}

			if ($el->kind == 'button') {
				$data['content'] = $el->label;
			}

			$label = $el->has_label() ? \Stag::label(array(
				"class"   => array('input-label', 'input-label-'.($label_on_right ? 'right':'left')),
				"content" => $el->label.($label_on_right ? '':':'),
				"for"     => $el->id,
			)):'';

			if ($el->type === 'rte') {
				$el->form->content_for('styles', 'pwf/form/rte');
				$el->form->content_for('scripts', 'pwf/lib/rte');
				$data['class'] = array_merge((array) $el->class, array('rte'));
			}

			if ($el->multiple && in_array($el->type, array('checkbox', 'radio'))) {

				$input = self::get_multi_input_html($el);

			} elseif ($el->type === 'search_tool') {

				$el->form()->content_for('scripts', 'pwf/form/search_tool');
				$el->form()->content_for('styles',  'pwf/form/search_tool');
				$input = self::get_search_tool_html($el);

			} elseif ($el->type === 'image') {

				$input = self::get_image_input_html($el);

			} elseif ($el->type === 'location') {

				$el->form()->content_for('scripts', 'pwf/form/autocompleter');
				$el->form()->content_for('scripts', 'pwf/form/location_picker');
				$el->form()->content_for('styles',  'pwf/form/autocompleter');
				$input = self::get_location_input_html($el);

			} else {

				if (in_array($el->type, array('datetime', 'date', 'time'))) {
					$el->form()->content_for('scripts', 'pwf/form/datetime_picker');

					if ($el->value instanceof \DateTime) {
						$tz = new \DateTimeZone('UTC');
						$el->value->modify('+'.$el->value->getOffset().' seconds');
						$el->value->setTimezone($tz);
						$data['value'] = format_date($el->value, 'html5-full');
					}
				}

				$html_element = $el->kind;

				if ($el->type === 'password' || $el->type === 'textarea') {
					unset($data['value']);
				}

				if ($el->type === 'checkbox' && !$el->value) {
					$data['value'] = true;
				}

				$input = \Tag::div(array("content" => \Tag::$html_element($data), "class" => array('input-container'), "output" => false));
			}

			$info = '';

			if ($el->info) {
				$info = \Tag::span(array(
					"class"   => 'input-info',
					"content" => $el->info,
					"output"  => false,
				));
			}

			$errors = '';
			$error_list = $el->form()->get_errors($el->name);

			if (!empty($error_list)) {
				$error_list_attrs = array(
					"content" => array(),
					"class"   => 'errors',
					"output"  => false,
				);

				foreach ($error_list as $e) {
					$error_list_attrs['content'][] = \Tag::li(array("content" => $e, "output"  => false));
				}

				$errors = \Stag::ul($error_list_attrs);
			}

			$label_and_input = $label_on_right ? $input.$label:$label.$input;
			return $label_and_input.$info.$errors;
		}


		private static function is_label_on_right($el)
		{
			return !$el->multiple && in_array($el->type, array('checkbox', 'radio'));
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
			} elseif ($el instanceof \System\Form\Text) {
				$base_class = 'text';
			}

			$class[] = 'form-'.$base_class;
			return $class;
		}


		/** Render inputs group
		 * @param \System\Form\Container $el Group instance
		 */
		private static function render_container_inputs(\System\Template\Renderer $ren, \System\Form\Container $el)
		{
			$output = array();

			if ($el->label) {
				$output = div('group_label', $el->label);
			}

			$attrs = $el->get_data();
			foreach ($el->get_elements() as $name=>$object) {
				$attrs['content'][] = li(self::render_element($ren, $object), self::get_object_class($object));
			}

			return \Stag::fieldset(array(
				"class"   => $el->type.'_container',
				"content" => \Stag::ul($attrs),
			));
		}


		/** Render tab group
		 * @param \System\Form\Container $el Group instance
		 */
		private static function render_container_tab_group(\System\Form\Container $el)
		{
			$el->renderer()->content_for('styles', 'pwf/form/tabs');
			$el->renderer()->content_for('scripts', 'pwf/form/tab_manager');
			$output = array();

			foreach ($el->get_elements() as $el) {
				$output[] = self::render_element($ren, $el);
			}

			return div(array('tab_group', $el->name), $output);
		}


		/** Render tab
		 * @param \System\Form\Container $el Tab instance
		 */
		private static function render_container_tab(\System\Form\Container $el)
		{
			$output = array();

			foreach ($el->get_elements() as $el) {
				$output[] = self::render_element($ren, $el);
			}

			return div(array('tab', $el->name), array(
				div('tab_label', $el->label),
				div('tab_content', $output),
			));
		}
	}
}
