<?

Tag::doctype();
Tag::html();

	Tag::head();
		content_for('styles', 'pwf/base');
		content_from('head');
	Tag::close('head');

	Tag::body();

		Tag::section(array(
			"id" => 'container',
			"output" => true,
		));

			yield();
			slot();

		Tag::close('section', true);
		Tag::footer(array("content" => System\Output::introduce()));

	Tag::close('body');
Tag::close('html');
