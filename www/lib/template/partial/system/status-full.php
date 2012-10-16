<?

Tag::div(array("class" => 'status-report'));
	echo heading(l('System status'));

	foreach ($status as $section_name=>$section_data) {
		echo heading($section_name, false);

		Tag::ul();
			foreach ($section_data as $var=>$val) {
				Tag::li(array(
					"content" => array(
						Tag::strong(array("content" => $var.': ', "output" => false)),
						Tag::span(array("content" => $val['message'], "output" => false)),
					),
					"class" => $val['class'],
				));
			}
		Tag::close('ul');
	}

Tag::close('div');
