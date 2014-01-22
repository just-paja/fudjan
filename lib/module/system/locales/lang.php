<?

$this->req('lang');
$locales->set_locale($lang);
$ren->partial('system/common', array('json_data' => $locales->get_messages($lang)));

