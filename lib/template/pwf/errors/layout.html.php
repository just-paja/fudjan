<?

echo doctype();
echo html($locales->get_lang());

	echo head();
		$ren->content_for('styles', 'styles/pwf/fonts');
		$ren->content_for('styles', 'styles/pwf/errors');
		$ren->content_from('head');
	close('head');

	echo body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			$this->yield();
			$this->slot();

		close('section');
		Tag::footer(array("content" => \System\Status::introduce()));
	close('body');
close('html');
