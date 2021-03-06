<?php

$policy = function($rq, $res) {
	$serial = \System\Resource::get_serial();

	try {
		$static_domain = \System\Settings::get('resources', 'domain');
	} catch (\System\Error\Config $e) {
		$static_domain = null;
	}

	try {
		$locales_url = ($static_domain ? '//'.$static_domain:'').$res->url("system_resource", array('static', 'locale', '{lang}.'.$serial.'.json'));
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

	$now = new \DateTime();
	$tz  = new \DateTimeZone(\System\Settings::get('locales', 'timezone'));

	$now->setTimeZone($tz);

	$cont = array(
		"locales" => array(
			"url"      => $locales_url,
			"lang"     => $res->locales->get_lang(),
			"autoload" => $autoload,
			"tz"       => -$tz->getOffset($now)/60,
			"now"      => $now->format('c')
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
