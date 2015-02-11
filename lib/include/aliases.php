<?

/** Function aliases that make life easier
 * @package core
 */
namespace
{
	// BasicModel & ExtModel aliases
	/** @alias System\Model\Database::get_all */
	function get_all($model, array $conds = array(), array $opts = array(), array $joins = array()) {
		return System\Model\Database::get_all($model, $conds, $opts, $joins);
	}

	/** @alias System\Model\Database::get_first */
	function get_first($model, array $conds = array(), array $opts = array(), array $joins = array()) {
		return System\Model\Database::get_first($model, $conds, $opts, $joins);
	}

	/** @alias System\Model\Database::count */
	function count_all($model, array $conds = array(), array $opts = array(), array $joins = array()) {
		return System\Model\Database::count_all($model, $conds, $opts, $joins);
	}

	/** @alias System\Model\Database::find */
	function find($model, $ids = array(), $force_array = false) {
		return System\Model\Database::find($model, $ids, $force_array);
	}

	/** @alias System\Model\Database::create */
	function create($model, array $attrs) {
		return System\Model\Database::create($model, $attrs);
	}


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
