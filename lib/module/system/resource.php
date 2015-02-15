<?

/** Module that sends resource content
 * @package modules
 */

$this->req('res_src');
$this->req('res_type');
$this->req('res_path');

$resource = \System\Resource::sort_out(array(
	'request'  => $request,
	'response' => $response,
	'path' => $res_path,
	'src'  => $res_src,
	'type' => $res_type,
));

$resource->serve();
