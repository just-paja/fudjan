<?

$url = $request->get('url');

$data = array(
	"status" => 404,
	"url" => $url
);

if ($url) {
	$response = \System\Offcom\Request::get($url, true);

	$data['status'] = $response->status;
	$data['headers'] = $response->headers;
	$data['size'] = $response->size;
	$data['type'] = $response->mime;
}

$ren->partial('system/common', array('json_data' => $data));
