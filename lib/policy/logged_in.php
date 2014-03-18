<?

$policy = function($rq, $res) {
	if ($rq->user->id) {
		return true;
	}

	throw new \System\Error\AccessDenied('You must log in to proceed');
};
