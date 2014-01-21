<?

$url = $request->get('url');

$data = array(
	"status" => 404,
	"url" => $url
);

if ($url) {
	$response = \System\Offcom\Request::get($url, true);

	$data['status'] = $response->status;
	$data['headers'] = array();
	$headers = explode("\n", $response->headers);

	foreach ($headers as $row) {
		$row = trim($row);

		if (strpos($row, ':') > 0) {
			$row = explode(":", $row, 2);
			$name = trim($row[0]);
			$content = trim($row[1]);
			$data['headers'][$name] = $content;
		}
	}
}

if (isset($data['headers']['Content-Length'])) {
	$data['size'] = $data['headers']['Content-Length'];
}

if (isset($data['headers']['Content-Type'])) {
	$data['type'] = strtolower($data['headers']['Content-Type']);
}

$ren->partial('system/common', array('json_data' => $data));
