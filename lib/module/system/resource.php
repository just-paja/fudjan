<?

/** Module that displays partial without locals
 * @package modules
 */

//~ v($res_type);
//~ v($res_path);
//~ exit;

$this->req('res_type');
$this->req('res_path');

\System\Resource::request($response, $res_type, $res_path);
