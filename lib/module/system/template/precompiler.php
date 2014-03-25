<?

$templates = array();
$load = \System\Composer::list_files('/lib/template/frontend');

foreach ($load as $template) {
	$name = basename($template);
	$templates[$name] = trim(preg_replace("/>\s+</", "><", \System\File::read($template)));
}

$this->partial(null, array(
	'data'   => $templates,
	'status' => 200
));
