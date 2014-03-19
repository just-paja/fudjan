<?

echo doctype();
echo html($loc->get_lang());

	echo head();
		$ren->content_for('styles', 'styles/pwf/fonts');
		$ren->content_for('styles', 'styles/pwf/errors');
		$ren->content_for('title', 'Fudjan error');
		$ren->content_from('head');
	close('head');

	echo body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			$ren->yield();
			$ren->slot();

		close('section');
		Tag::footer(array("content" => \System\Status::introduce()));
	close('body');
close('html');
