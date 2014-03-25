<?

$this->req('lang');
$locales->set_locale($lang);
$ren->partial(null, $locales->get_messages($lang));

