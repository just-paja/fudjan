<?

$this->req('id');
def($template, 'system/text/show');
def($show_heading, true);

if ($id && $text = find('\System\Text', $id)) {

	$this->partial($template, array(
		"text" => $text,
		"show_heading" => $show_heading,
	));

} else {
	if (cfg('dev', 'debug', 'backend')) {
		throw new \System\Error\Config('Text was not found', $id);
	} else {
		throw new \System\Error\NotFound();
	}
}
