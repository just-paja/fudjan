<?


echo div('system-text', array(
	$show_heading ? $ren->heading($text->name):'',
	div('content', to_html($ren->renderer, $text->text)),
));
