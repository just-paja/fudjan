<?

Tag::doctype();
Tag::html();

	Tag::head();
		content_for('styles', 'installer');
		content_from('head');
	Tag::close('head');

	Tag::body();

		Tag::section(array(
			"id" => 'container',
			"output" => true,
		));

			yield();
			System\Setup::run();
			slot();

		Tag::close('section', true);
		Tag::footer(array("content" => System\Output::introduce()));

	Tag::close('body');
Tag::close('html');
