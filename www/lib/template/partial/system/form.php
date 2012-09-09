<?


if (!defined("EDITOR_HAS_TEMPLATE_HEADER")) {
	define('EDITOR_HAS_TEMPLATE_HEADER', true);

	function form_error($msg){ return '<span class="form-error">'.$msg.'</span>'; }
}


$sub_li = $li = 0;

Tag::div(array(
	"class"  => 'plain-form',
	"id"     => $f->get('id').'-container',
	"output" => true,
));

	!empty($object['anchor']) &&
		Tag::a(array(
			"name" => $object['anchor'],
			"close" => true,
			"output" => true,
		));

	if ($f->get('heading'))
		echo section_heading($f->get('heading'));

	$f->get('desc') &&
		Tag::p(array(
			"content" => $f->get('desc'),
			"close"   => true,
			"output"  => true,
		));


	Tag::form($f->get());

		$hidden = $f->get_hidden();

		if (!empty($hidden)) {
			Tag::fieldset(array("class" => 'hidden'));

				foreach ($hidden as $obj) {
					Tag::input($obj);
				}

			Tag::close('fieldset');
		}

		$objects = &$f->get_objects();

		foreach ($objects as $obj) {

			def($obj['value'], '');
			def($obj['errors'], array());
			def($obj['required'], false);

			switch ($obj['kind']) {

				case 'inputs-start': {
					Tag::fieldset(array(
						"close"   => false,
						"class"   => 'inputs',
						"content" => Tag::ul(array(
							"class" => 'inputs',
							"output" => false,
						)),
					));
					break;
				}


				case 'inputs-end': {
					Tag::li(array(
						"style"  => 'clear:both;width:0px;height:0px;float:none;display:block',
						"close"  => true,
					));

					Tag::close('ul');
					Tag::close('fieldset');
					break;
				}


				case 'tabs-group-start': {

					content_for("scripts", "jquery.ui");
					Tag::div(array(
						"class" => 'tabs',
						"id"    => $obj['id'],
					));
						Tags::ul();

							foreach ($obj['tabs'] as $tab) {
								Tag::li(array(
									"content" => Tag::a(array(
										"href" => '#'.$tab['id'],
										"content" => $tab['title'],
									)),
								));
							}

						Tags::close('ul');
					break;
				}


				case 'tab-start': {
					Tag::div($obj);
					break;
				}


				case 'tabs-group-end':
				case 'tab-end': {
					Tag::close('div');
					break;
				}


				case 'input': {
					$multi_checkbox = ($obj['type'] == 'checkbox' && isset($obj['options']) && is_array($obj['options']));

					if (strpos($obj['label'], ':::')) {
						$obj['label'] = str_replace(':::', null, $obj['label']);
						$sep = '';
					} else {
						$sep = ':';
					}

					if ($obj['value'] instanceof DateTime) {
						$obj['value'] = format_date($obj['value'], "html5");
					}

					Tag::li(array(
						"title" => $obj['required'] ? l('This field is required'):'',
						"class" => array(
							"form-li-".(++$li),
							$obj['type'],
							$obj['required'] ? 'required':'',
							$obj['eclass'],
						)
					));

						if ($obj['type'] != 'checkbox' || ($multi_checkbox && $obj['type'] == 'checkbox')) {
							Tag::label(array(
								"for"     => $obj['id'],
								"content" => $obj['label'].$sep,
								"class"   => array(
									'label-left',
									$multi_checkbox ?'label-multi-checkbox':''
								),
							));
						}

						if ($obj['type'] == 'radio' || $multi_checkbox) {
							Tag::ul($obj);

								$i = 0;
								foreach ($obj['options'] as $label=>$val) {
									$i++;
									$iid = $obj['name'].'_input_'.$i;

									Tag::li(array(
										"class" => array(
											'form-subli',
											'form-subli-'.(++$sub_li)
										),
										"content" => array(
											Tag::input(array(
												"type"    => $obj['type'],
												"name"    => $name['name'].($obj['type'] == 'checkbox' ? '[]':''),
												"value"   => $val,
												"id"      => $iid,
												"checked" => ($obj['value'] == $val || (is_array($obj['value']) && in_array($val, $obj['value']))) || (is_object($obj['value']) && $val == $obj['value']->id),
												"output"  => false
											)),
											Tag::label(array(
												"class"   => 'label-right',
												"for"     => $iid,
												"content" => $label,
											))
										)
									));
								}

							Tag::close('ul');
						} else {

							$obj['output'] = false;
							$obj['close']  = true;

							Tag::span(array(
								"class"   => 'form-input',
								"content" => Tag::input($obj),
							));

							if ($obj['type'] == 'checkbox') {
								Tag::label(array(
									"class"   => 'label-right',
									"for"     => $obj['id'],
									"content" => $obj['label'],
								));
							}
						}

						if (is_array($obj['errors'])) {
							foreach ($obj['errors'] as $e) {
								echo form_error($e);
							}
						}

						Tag::close('li');
					break;
				}


				case 'textarea': {
					$attrs = $obj;
					unset($attrs['value']);
					$attrs['content'] = $obj['value'];
					$attrs['output'] = false;

					Tag::li(array(
						"title" => $obj['required'] ? l('This field is required'):'',
						"class" => array(
							'form-li-'.(++$li),
							"textarea",
							$obj['required'] ? 'required':'',
							$obj['eclass'],
						),
						"content" => array(
							Tag::label(array(
								"class"   => 'label-left',
								"output"  => false,
								"content" => $obj['label'],
							)),
							Tag::span(array(
								"output"  => false,
								"class"   => 'form-input',
								"content" => Tag::textarea($attrs),
							)),
						)
					));
					break;
				}


				case 'select': {
					if (isset($obj['multiple']) && $obj['multiple']) $obj['name'] .= '[]';

					Tag::li(array(
						"title" => $obj['required'] ? l('This selection is required'):'',
						"class" => array(
							'form-li-'.(++$li),
							"select",
							$obj['required'] ? 'required':'',
							$obj['eclass'],
						),
					));

						Tag::label(array(
							"class"   => 'label-left',
							"for"     => $obj['id'],
							"content" => $obj['label'],
						));

						Tag::select($obj);
							foreach($obj['options'] as $opt=>$val){
								if (is_array($val)) {
									Tag::optgroup(array(
										"label" => $opt,
									));

										foreach ($val as $l=>$v) {
											Tag::option(array(
												"value" => $v,
												"selected" => in_array($v, (array) $obj['value']),
												"content" => $l,
											));
										}

										Tag::close('optgroup');
								} else {
									Tag::option(array(
										"value" => $val,
										"selected" => in_array($val, (array) $obj['value']),
										"content" => $opt,
									));
								}
							}
						Tag::close('select');

						if (isset($obj['entry']) && $obj['entry']) {
							Tag::div(array(
								"class"   => 'select-entry',
								"content" => Tag::input(array(
									"type"   => 'text',
									"name"   => $obj['name'].'-entry',
									"value"  => $obj['value'],
									"style"  => 'display:none',
									"close"  => true,
									"output" => false,
								)),
							));
						}

					Tag::close('li');
					break;
				}


				case 'separator': {
					Tag::span(array("class" => 'form-separator'));
					break;
				}


				case 'text': {
					Tag::li(array(
						"class"   => array(
							'form-li-'.(++$li),
							'formel-text',
						)
					));

						if ($obj['label']) {
							Tag::label(array(
								"class"   => 'label-left',
								"content" => $obj['label'],
							));
						}

						Tag::p(array(
							"class" => 'form-text',
							"content" => $obj['text'],
						));

					Tag::close('li');
					break;
				}


				case 'tip': {
					Tag::li(array(
						"class"   => array(
							'form-li-'.(++$li),
							'formel-text',
						),
						"content" => array(
							Tag::label(array(
								"output" => false,
								"close"  => true,
							)),
							Tag::p(array(
								"class"   => 'form-tip',
								"content" => $obj['text'],
								"output"  => false,
							)),
						)
					));
					break;
				}


				case 'label': {
					Tag::li(array(
						"class"   => array(
							'form-li-'.(++$li),
							'formel-text',
						),
						"content" => Tag::label(array(
							"output"  => false,
							"content" => $obj['text'],
						))
					));
					break;
				}


				case 'group-start': {
					def($obj['class'], '');
					$class_val = array(
						'form-li-'.(++$li),
						'formel-group',
						'form-group'.($obj['class'] ? '-'.$obj['class']:'').'-cont'
					);

					echo '<li class="'.implode(' ', $class_val).'"><ul class="form-group'.($obj['class'] ? '-'.$obj['class']:'').'">';
					break;
				}


				case 'group-end': {
					Tag::close('ul');
					Tag::close('li');
					break;
				}


				case 'html': {
					echo $obj['html'];
					break;
				}


				case 'button': {
					$obj['content'] = Tag::span(array(
						"output"  => false,
						"content" => $obj['label'],
						"close"   => true
					));

					Tag::button($obj);
					break;
				}


				case 'clear': {
					Tag::span(array("class" => 'clear'));
					break;
				}
			}
		}

		if (!empty($f->footnote)) {
			Tag::p(array(
				"content" => implode('<br />', (array) $f->footnote),
			));
		}

	Tag::close('form');
Tag::close('div');

if(!empty($f->form_js)){
	?>
	<script type="text/javascript">
		//<![CDATA[
			$(function() {
				<?
				if(is_array($f->form_js)){
					foreach($f->form_js as $line){
						echo $line."\n";
					}
				}
				?>
			});
		//]]>
	</script>
<?
}
