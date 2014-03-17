<?

echo doctype();
echo html($ren->lang());
	echo head($ren->content_from('head'));

	echo body();

		echo div('container');
			$this->yield();
			$this->slot();
		close('div');

		echo footer('std', \System\Status::introduce());

	close('body');
close('html');
