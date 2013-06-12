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
			$content = array();

			if ($form->heading) {
				$content[] = $ren->heading($form->heading);
			}

			if ($form->desc) {
				$content[] = \Stag::p(array("content" => $form->desc));
			}

			$content[] = \Stag::form($form_attrs);
			return div(array_merge(array('pwform'), (array) $form->class), $content, $form->id);
		}


		public static function render_element(\System\Template\Renderer $ren, \System\Form\Element $el)
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
				case 'System\Form\Label': return self::render_label($el);
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


		/** Render text element
		 * @param \System\Form $form
		 * @param string       $el
		 * @param mixed        $for
		 * @return string
		 */
		public static function label(\System\Form $form, $text, $for = null)
		{
			return self::render_label(new \System\Form\Label(array(
				"content" => $text,
				"form"    => $form,
				"for"     => $for,
			)));
		}


		/** Render text element
		 * @param \System\Template\Renderer $ren
		 * @param \System\Form\Text         $el
		 * @return string
		 */
		private static function render_text(\System\Template\Renderer $ren, \System\Form\Text $el)
		{
			return
				div('form-text', array(
					self::label($el->form(), $el->label),
					div(array('input-container'), $el->content)
				));
		}


		/** Render widget
		 * @param \System\Form\Widget $el Widget instance
		 * @return string|null
		 */
		public static function render_widget(\System\Template\Renderer $ren, \System\Form\Widget $el)
		{
			$el->use_resources($ren);

			$tools      = $el->get_tools();
			$inputs     = array();
			$tools_html = null;

			if (count($tools) > 1) {
				foreach ($tools as $tool) {
					$tool_html = self::render_element($ren, $tool);

					if (!is_null($tool_html)) {
						$inputs[] = li($tool_html, self::get_object_class($tool));
					}
				}

				$tools_html = ul('widget-tools', $inputs);
			} else if (any($tools)) {
				$keys = array_keys($tools);
				$tools_html = self::render_element($ren, $tools[$keys[0]]);
			}

			$errors = self::render_error_list($ren, $el);

			if (is_null($tools_html)) {
				return null;
			} else {
				$content = array(self::label($el->form(), $ren->trans($el->label)), $tools_html, $errors);
				return div('input-container input-'.$el::IDENT, $content);
			}
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
				$data['content'] = self::render_select_opts($ren, $el);
				unset($data['options']);
			}

			if ($el->kind == 'button') {
				$data['content'] = $el->label;
			}

			$label = $el->has_label() ? \Stag::label(array(
				"class"   => array('input-label', 'input-label-'.($label_on_right ? 'right':'left')),
				"content" => $ren->trans($el->label).($label_on_right ? '':':'),
				"for"     => $el->id,
			)):'';

			if ($el->multiple && $el->type == 'checkbox' || $el->type == 'radio') {

				$input = self::render_multi_input_html($ren, $el);

			} else {

				if (in_array($el->type, array('date', 'time'))) {

					if ($el->value instanceof \DateTime) {
						$tz = new \DateTimeZone('UTC');
						$el->value->modify('+'.$el->value->getOffset().' seconds');
						$el->value->setTimezone($tz);

						if ($el->type == 'date') {
							$data['value'] = $ren->format_date($el->value, 'sql-date', \System\Locales::TRANS_NONE);
						}

						if ($el->type == 'time') {
							$data['value'] = $ren->format_date($el->value, 'sql-time', \System\Locales::TRANS_NONE);
						}
					}
				}

				$html_element = $el->kind;

				if ($el->type === 'password' || $el->type === 'textarea') {
					unset($data['value']);
				}

				if ($el->type === 'checkbox' && !$el->value) {
					$data['value'] = true;
				}

				if (any($data['value']) && gettype($data['value']) == 'double') {
					$data['value'] = number_format($data['value'], 15);
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

			$errors = self::render_error_list($ren, $el);

			$label_and_input = $label_on_right ? $input.$label:$label.$input;
			return $label_and_input.$info.$errors;
		}


		public static function render_error_list(\System\Template\Renderer $ren, \System\Form\Element $el)
		{
			$errors = '';
			$error_list = $el->form()->get_errors($el->name);


			if (any($error_list)) {
				$error_lis = array();

				foreach ($error_list as $e) {
					$error_lis[] = li($e);
				}

				$errors = ul('errors', $error_lis);
			}

			return $errors;
		}


		public static function render_select_opts(\System\Template\Renderer $ren, \System\Form\Input $el)
		{
			$opts = array();

			if (!$el->required) {
				$opts[] = \Stag::option(array(
					"content"  => ' - - - ',
					"value"    => '',
					"selected" => !$el->value,
				));
			}

			foreach ($el->options as $id=>$opt) {
				if (is_object($opt)) {
					if ($opt instanceof \System\Model\Attr) {
						$label = $opt->get_name();
						$id    = $opt->id;
					} else throw new \System\Error\Form(sprintf("Form options set passed as object must inherit System\Model\Attr. Instance of '%s' given.", get_class($opt)));
				} else {
					$label = $opt;
				}

				$opts[] = \Stag::option(array(
					"content"  => $ren->trans($label),
					"value"    => $id,
					"close"    => true,
					"selected" => $el->value == $id,
				));
			}

			return $opts;
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
			} elseif ($el instanceof \System\Form\Widget) {
				$base_class = 'widget';
				$class[] = 'widget-'.$el::IDENT;
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
			$label = '';

			if ($el->label) {
				$label = div('group_label', $el->label);
			}

			$attrs = $el->get_data();
			foreach ($el->get_elements() as $name=>$object) {
				$element_html = self::render_element($ren, $object);

				if (!is_null($element_html)) {
					$attrs['content'][] = li($element_html, self::get_object_class($object));
				}
			}

			$content = empty($attrs['content']) ? null:\Stag::ul($attrs);

			return (is_null($content) && is_null($label)) ? null:\Stag::fieldset(array(
				"class"   => array_merge($el->class_outer, array($el->type.'_container', 'group_'.$el->name)),
				"content" => array($label, $content)
			));
		}


		/** Render tab group
		 * @param \System\Form\Container $el Group instance
		 */
		private static function render_container_tab_group(\System\Template\Renderer $ren, \System\Form\Container $el)
		{
			$ren->content_for('styles', 'pwf/form/tabs');
			$ren->content_for('scripts', 'pwf/form/tab_manager');
			$output = array();

			foreach ($el->get_elements() as $el) {
				$output[] = self::render_element($ren, $el);
			}

			return div(array('tab_group', $el->name), $output);
		}


		/** Render tab
		 * @param \System\Form\Container $el Tab instance
		 */
		private static function render_container_tab(\System\Template\Renderer $ren, \System\Form\Container $el)
		{
			$output = array();

			foreach ($el->get_elements() as $group) {
				$output[] = self::render_element($ren, $group);
			}

			return div(array_merge($el->class, $el->class_outer, array($el->name)), array(
				div('tab_label', $el->label),
				div('tab_content', $output),
			));
		}


		private static function render_multi_input_html(\System\Template\Renderer $ren, \System\Form\Input $el)
		{
			$input = array();
			$opts = array();
			$iname = $el->type === 'radio' ?
				$el->form()->get_prefix().$el->name:
				$el->form()->get_prefix().$el->name.'[]';

			foreach ($el->options as $id=>$opt) {
				if (is_object($opt)) {
					if ($opt instanceof \System\Model\Attr) {
						$id  = $opt->id;
						$lbl = $opt->name;
					} else throw new \System\Error\Form('Form options set passed as object must inherit System\Model\Attr');
				} else {
					$lbl = $opt;
				}

				$opts[] = li(array(
					div('input-container', \Stag::input(array(
						"name"    => $iname,
						"id"      => $el->form()->get_prefix().$el->name.'_'.$id,
						"value"   => $id,
						"type"    => $el->type,
						"checked" => is_array($el->value) && in_array($id, $el->value) || $el->value == $id,
					))),
					\Stag::label(array(
						"content" => $ren->trans($lbl),
						"for"     => $el->form()->get_prefix().$el->name.'_'.$id,
					)),
				), 'input-left');
			}

			return ul('options', $opts);
		}
	}
}
