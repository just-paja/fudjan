<?php

$policy = function($rq, $res) {
	if (strpos($rq->host, 'www') === 0) {
		return true;
	}

	$redir = ($rq->secure ? 'https':'http').'://www.'.$rq->host.$rq->path.($rq->query ? '?'.$rq->query:'');
	redirect_now($redir, \System\Http\Response::MOVED_PERMANENTLY);
	return false;
};
