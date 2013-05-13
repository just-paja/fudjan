<?

echo doctype();
echo html(\System\Locales::get_lang());
	echo head($renderer->content_from('head'));

	echo body();

		echo div('container');
			$this->yield();
			$this->slot();
		close('div');

		echo footer('std', introduce());

	close('body');
close('html');
