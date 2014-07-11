<?

$policy = function($rq, $res) {
	try {
		$static_domain = \System\Settings::get('resources', 'domain');
	} catch (\System\Error\Config $e) {
		$static_domain = null;
	}

	try {
		$locales_url = ($static_domain ? '//'.$static_domain:'').$this->url("locale_list");
	} catch (\System\Error\NotFound $e) {
		$locales_url = '';
	}

	try {
		$autoload = \System\Settings::get('locales', 'autoload');
	} catch (\System\Error\Config $e) {
		$autoload = false;
	}

	try {
		$debug = \System\Settings::get('dev', 'debug');
	} catch (\System\Error\Config $e) {
		$debug = array(
			'frontend' => true,
			'backend'  => true
		);
	}

	$cont = array(
		"locales" => array(
			"url"      => substr($locales_url, 0, strlen($locales_url)-1),
			"lang"     => $res->locales()->get_lang(),
			"autoload" => $autoload,
		),
		"comm" => array(
			"blank" => '/share/html/blank.html'
		),
		"debug" => $debug,
		"proxy" => array(
			'url' => '/proxy/head/?url={url}'
		),
	);

	try {
		$frontend = \System\Settings::get('frontend');
	} catch(\System\Error $e) {
		$frontend = array();
	}

	$rq->fconfig = array_merge_recursive($cont, $frontend);
	return true;
};
