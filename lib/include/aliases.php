<?

/** Function aliases that make life easier
 * @package core
 */
namespace
{
	// Redirects
	/** @alias System\Http\Response::redirect */
	function redirect_now($url, $code=\System\Http\Response::FOUND) {
		return System\Http\Response::redirect($url, $code);
	}


	// Template
	/** @alias System\Settings::get */
	function cfg() {
		return System\Settings::get(func_get_args());
	}

	/** @alias System\Settings::set */
	function cfgs(array $path, $value) {
		return System\Settings::set($path, $value);
	}
}
