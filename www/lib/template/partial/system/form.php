<?

$renderer->content_for('styles', 'pwf/form');

if ($response) {
	!$this->action && $this->action = $this->response->path();
}


$sub_li = $li = 0;
$cclass = array();

foreach ((array) $f->class as $c) {
	$cclass[] = $c.'_outer';
}

Tag::div(array("id" => $f->id.'-container', "class" => array_merge(array('pwform'), $cclass)));

	Tag::a(array("name" => $f->anchor, "id" => $f->anchor, "close" => true));
	$f->heading && print(section_heading($f->heading));
	$f->desc && Tag::p(array("class" => 'desc', "content" => $f->desc));

	Tag::form($f->get_attr_data());

		Tag::fieldset(array(
			"class" => 'hidden',
			"content" => Tag::input(array(
				"value"  => htmlspecialchars(json_encode($f->get_hidden_data())),
				"type"   => 'hidden',
				"name"   => $f->get_prefix().'data_hidden',
				"close"  => true,
				"output" => false,
			)),
		));

		$objects = $f->get_objects();

		foreach ($objects as $obj) {
			System\Form\Helper::render_element($obj);
		}

		echo div('cleaner', '');
	Tag::close('form');
Tag::close('div');


if (!empty($f->form_js)) {
	Tag::script(array(
		"type"    => 'text/javascript',
		"content" => '$(function() { '.implode("\n", $f->form_js).' });',
	));
}
