<?

$policy = function($rq, $res) {
	if (strpos($rq->host, 'www') !== 0) {
		return true;
	}

	$host = preg_replace('/^www\./', '', $rq->host);
	$redir = ($rq->secure ? 'https':'http').'://'.$host.$rq->path.($rq->query ? '?'.$rq->query:'');
	redirect_now($redir, \System\Http\Response::MOVED_PERMANENTLY);
	return false;
};
