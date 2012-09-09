<?

echo '<!DOCTYPE html>';
echo '<html xmlns="http://www.w3.org/1999/xhtml">';

echo Tag::head();
	echo Tag::link(array(
		"rel"   => 'stylesheet',
		"type"  => 'text/css',
		"href"  => '/share/styles/default.css',
		"close" => true,
	));

	content_from('head');

echo Tag::close('head');

echo Tag::body();
	echo Tag::div(array(
		"id" => 'container'
	));
		yield();
		slot();

	Tag::close('div', true);
	
	Tag::footer('footer', array(
		"content" => System\Output::introduce(),
		"close" => true,
	));

	Tag::close('body', true);
Tag::close('html', true);
