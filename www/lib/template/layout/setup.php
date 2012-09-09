<?

echo '<!DOCTYPE html>';
echo '<html xmlns="http://www.w3.org/1999/xhtml">';

echo Tag::head();

	content_for('styles', 'installer');
	content_from('head');

Tag::close('head');

echo Tag::body();
	
	Tag::header(array(
		"output" => true,
		"close"  => true,
	));

	Tag::section(array(
		"id" => 'container',
		"output" => true,
	));

		yield();
		System\Setup::run();
		slot();

	Tag::close('section', true);

	Tag::footer(array(
		"content" => System\Output::introduce(),
		"close" => true,
		"output" => true,
	));

	Tag::close('body');
Tag::close('html');
