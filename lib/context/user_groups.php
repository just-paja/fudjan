<?php

$context = function($rq, $res) {
	if ($rq->user) {
		return array(
			'groups' => $rq->user->groups->fetch()
		);
	}

	throw new \System\Error\AccessDenied('User is not logged in.');
};
