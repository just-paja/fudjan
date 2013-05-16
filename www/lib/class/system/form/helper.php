<?

namespace System\Form
{
	abstract class Helper
	{
		public static function error($msg)
		{
			return '<span class="form-error">'.$msg.'</span>';
		}


		private static function is_label_on_right($el)
		{
			return !$el->multiple && in_array($el->type, array('checkbox', 'radio'));
		}


		public static function render_input(\System\Form\Input $el, $output = true)
		{
			$el->content = $el->is_value_content() ? $el->value:$el->content;
			$label_on_right = self::is_label_on_right($el);

			$data = $el->get_data();
			$data['output'] = false;
			$data['close']  = true;
			$data['name']   = $el->get_form()->get_prefix().$data['name'];

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

				$el->get_form()->content_for('scripts', 'pwf/form/search_tool');
				$el->get_form()->content_for('styles',  'pwf/form/search_tool');
				$input = self::get_search_tool_html($el);

			} elseif ($el->type === 'image') {

				$input = self::get_image_input_html($el);

			} elseif ($el->type === 'location') {

				$el->get_form()->content_for('scripts', 'pwf/form/autocompleter');
				$el->get_form()->content_for('scripts', 'pwf/form/location_picker');
				$el->get_form()->content_for('styles',  'pwf/form/autocompleter');
				$input = self::get_location_input_html($el);

			} elseif ($el->type === 'gps') {
				$el->get_form()->content_for('scripts', 'pwf/form/jquery.gmap');
				$el->get_form()->content_for('scripts', 'pwf/form/gps');
				$input = self::get_gps_input_html($el);

			} else {

				if (in_array($el->type, array('datetime', 'date', 'time'))) {
					$el->get_form()->content_for('scripts', 'pwf/form/datetime_picker');

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
			if ($output) {
				echo $label_and_input.$info.$errors;
			} else return $label_and_input.$info.$errors;
		}


		public static function get_image_input_html(\System\Form\Input $el)
		{
			$inputs = array();

			if ($el->value && $el->value instanceof \System\Image) {
				list($w, $h) = explode('x', $el->thumb_size);
				$inputs[] = $el->form->renderer()->link_ext($el->value->get_path(), $el->value->to_html($w, $h));
			}

			$to = array("output" => false, "class" => 'inputs im-options', "content" => array());
			self::render_input_tools_into($to['content'], $el->tools);
			$inputs[] = \Tag::ul($to);

			return \Tag::div(array(
				"class" => array('input-container', 'input-image'),
				"output" => false,
				"content" => $inputs,
			));
		}


		public static function get_location_input_html(\System\Form\Input $el)
		{
			$inputs = array();
			$to = array("output" => false, "class" => 'inputs loc-options', "content" => array());
			self::render_input_tools_into($to['content'], $el->tools);
			$inputs[] = \Tag::ul($to);


			return \Tag::div(array(
				"class" => array('input-container', 'input-location'),
				"output" => false,
				"content" => $inputs,
			));
		}


		public static function get_gps_input_html(\System\Form\Input $el)
		{
			$inputs = array();
			$to = array("output" => false, "class" => 'inputs gps-options', "content" => array());

			self::render_input_tools_into($to['content'], $el->tools);
			$inputs[] = \Tag::ul($to);

			return \Tag::div(array(
				"class" => array('input-container', 'input-gps'),
				"output" => false,
				"content" => $inputs,
			));
		}


		private static function render_input_tools_into(&$target, $tools)
		{
			foreach ($tools as $tool) {
				$target[] = \Tag::li(array(
					"class"   => 'input',
					"output"  => false,
					"content" => self::render_input($tool, false),
				));
			}
		}


		public static function get_search_tool_html(\System\Form\Input $el)
		{
			return \Tag::div(array(
				"class" => array('input-container'),
				"output" => false,
				"content" => \Tag::div(array(
					"class"   => array('search_tool', 'search_tool_'.$el->name),
					"output"  => false,
					"content" => \Tag::span(array(
						"class"   => array('data', 'hidden'),
						"output"  => false,
						"style"   => 'display:none',
						"content" => json_encode(array(
							"name"        => $el->get_form()->get_prefix().$el->name,
							"model"       => $el->model,
							"conds"       => $el->conds,
							"display"     => $el->display,
							"filter"      => $el->filter,
							"has"         => $el->has,
							"placeholder" => $el->placeholder,
						)),
					)),
				)),
			));
		}


		public static function get_select_opts_html(\System\Form\Input $el)
		{
			$opts = array();

			if (!$el->required) {
				$opts[] = \Tag::option(array(
					"content" => ' - - - ',
					"value"   => '',
					"output"  => false,
					"selected" => !$el->value,
				));
			}

			foreach ($el->options as $id=>$opt) {
				if (is_object($opt)) {
					if ($opt instanceof \System\Model\Attr) {
						$label = $opt->get_name();
						$id    = $opt->id;
					} else throw new \System\Error\Form('Form options set passed as object must inherit System\Model\Attr');
				} else {
					$label = $opt;
				}

				$opts[] = \Tag::option(array(
					"content"  => $label,
					"value"    => $id,
					"close"    => true,
					"output"   => false,
					"selected" => $el->value == $id,
				));
			}

			return implode('', $opts);
		}


		public static function get_multi_input_html(\System\Form\Input $el)
		{
			$input = array();
			$opts = array();
			$iname = $el->type === 'radio' ?
				$el->get_form()->get_prefix().$el->name:
				$el->get_form()->get_prefix().$el->name.'[]';

			foreach ($el->options as $id=>$opt) {
				if (is_object($opt)) {
					if ($opt instanceof \System\Model\Attr) {
						$id  = $opt->id;
						$lbl = $opt->name;
					} else throw new \System\Error\Form('Form options set passed as object must inherit System\Model\Attr');
				} else {
					$lbl = $opt;
				}

				$opts[] = \Tag::li(array(
					"output"  => false,
					"content" => array(
						\Tag::input(array(
							"output"  => false,
							"name"    => $iname,
							"id"      => $el->get_form()->get_prefix().$el->name.'_'.$id,
							"value"   => $id,
							"type"    => $el->type,
							"checked" => is_array($el->value) && in_array($id, $el->value) || $el->value == $id,
						)),
						\Tag::label(array(
							"output"  => false,
							"content" => $lbl,
							"for"     => $el->get_form()->get_prefix().$el->name.'_'.$id,
						)),
					)
				));
			}

			return \Tag::ul(array(
				"class"   => 'options',
				"output"  => false,
				"content" => $opts,
			));
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
						case \System\Form\Container::TYPE_INPUTS:
						case \System\Form\Container::TYPE_BUTTONS:
						{
							\Tag::fieldset(array("class" => $el->type.'_container'));

								if ($el->label) {
									\Tag::div(array("class" => 'group_label', "content" => $el->label));
								}

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
						case \System\Form\Container::TYPE_TAB:
						{
							\Tag::div(array("class" => array('tab', $el->name)));
							\Tag::div(array("class" => 'tab_label', "content" => $el->label));
							\Tag::div(array("class" => 'tab_content'));

							foreach ($el->get_elements() as $el) {
								self::render_element($el);
							}

							\Tag::close('div');
							\Tag::close('div');
							break;
						}
						case \System\Form\Container::TYPE_TAB_GROUP:
						{
							$el->get_form()->content_for('styles', 'pwf/form/tabs');
							$el->get_form()->content_for('scripts', 'pwf/form/tab_manager');
							\Tag::div(array("class" => array('tab_group', $el->name)));

							foreach ($el->get_elements() as $el) {
								self::render_element($el);
							}

							\Tag::close('div');
							break;
						}
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
				case 'System\Form\Text':
				{
					self::render_label(new Label(array("content" => $el->label)));
					\Tag::div(array("class" => array('input-container'), "content" => $el->content));
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
			} elseif ($el instanceof \System\Form\Text) {
				$base_class = 'text';
			}

			$class[] = 'form-'.$base_class;
			return $class;
		}
	}
}

