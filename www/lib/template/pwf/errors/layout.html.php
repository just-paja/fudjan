<?

Tag::doctype();
Tag::html();

	Tag::head();
		$response->content_for('styles', 'pwf/base');
		$response->content_for('styles', 'pwf/errors');
		$response->content_from('head');
	Tag::close('head');

	Tag::body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			$this->yield();
			$this->slot();

		Tag::close('section');
		Tag::footer(array("content" => \System\Output::introduce()));
	Tag::close('body');
Tag::close('html');
