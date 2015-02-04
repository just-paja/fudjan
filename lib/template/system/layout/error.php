<?

echo doctype();
echo html($loc->get_lang());

	echo head();
		?>
		<link rel="stylesheet" type="text/css" href="<?=$ren->link_resource('style', 'error-report')?>">
		<?
		$ren->content_for('title', 'Fudjan error');
		$ren->content_from('head');
	close('head');

	echo body(array("class" => 'error'));
		Tag::section(array("id" => 'container'));

			$ren->render_yield();
			$ren->slot();

		close('section');
		Tag::footer(array("content" => \System\Status::introduce()));
	close('body');
close('html');
