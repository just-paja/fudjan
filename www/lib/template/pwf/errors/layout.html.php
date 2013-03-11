<?

Tag::doctype();
Tag::html();

	Tag::head();
		content_for('styles', 'pwf/base');
		content_for('styles', 'pwf/errors');
		content_from('head');
	Tag::close('head');

	Tag::body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			yield();
			slot();

		Tag::close('section');
		Tag::footer(array("content" => \System\Output::introduce()));
	Tag::close('body');
Tag::close('html');
