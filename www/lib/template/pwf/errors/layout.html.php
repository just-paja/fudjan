<?

Tag::doctype();
Tag::html();

	Tag::head();
		$renderer->content_for('styles', 'pwf/base');
		$renderer->content_for('styles', 'pwf/errors');
		$renderer->content_from('head');
	Tag::close('head');

	Tag::body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			$this->yield();
			$this->slot();

		Tag::close('section');
		Tag::footer(array("content" => \System\Output::introduce()));
	Tag::close('body');
Tag::close('html');
