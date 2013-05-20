<?

$renderer->content_for('styles', 'pwf/form');

if ($response) {
	!$this->action && $this->action = $this->response->path();
}


$sub_li = $li = 0;
$cclass = array();

foreach ((array) $f->class as $c) {
	$cclass[] = $c.'_outer';
}

echo \System\Form\Renderer::render($ren, $f);

//~ if (!empty($f->form_js)) {
	//~ Tag::script(array(
		//~ "type"    => 'text/javascript',
		//~ "content" => '$(function() { '.implode("\n", $f->form_js).' });',
	//~ ));
//~ }
