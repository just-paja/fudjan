<?

def($allow_jsonp, true);

if ($allow_jsonp && $request->get('callback')) {
	echo $request->get('callback').'('.json_encode($json_data).');';
} else {
	echo json_encode($json_data);
}

