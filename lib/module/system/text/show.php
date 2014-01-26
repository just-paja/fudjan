<?

$this->req('id');
def($template, 'system/text/show');
def($show_heading, true);

if ($id && $text = find('\System\Text', $id)) {

	$this->partial($template, array(
		"text" => $text,
		"show_heading" => $show_heading,
	));

} else throw new \System\Error\NotFound();
