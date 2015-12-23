<?php

$policy = function($rq, $res) {
	if ($rq->method == 'post') {
		return true;
	}

	throw new \System\Error\AccessDenied('Only GET method is allowed.');
};
