<?

/** Module used to search database and return data in JSON. Used by autosearch
 * @TODO Must rewrite for security reasons
 * @package modules
 */
//~ System\Output::set_format('json');
cfgs(array('dev', 'debug'), false);

$model   = System\Input::get('model');
$conds   = System\Input::get('conds');
$filter  = System\Input::get('filter');
$display = System\Input::get('display');
$fetch   = System\Input::get('fetch');
$value   = System\Input::get('value');
$has     = System\Input::get('has');
$limit   = System\Input::get('limit');

if ($model) {

	!is_array($display) && ($display = array('id', 'name'));
	!is_array($filter) && ($filter = array());
	!is_array($fetch) && ($fetch = array());
	!is_array($conds) && ($conds = array());
	!is_array($has) && ($has = array());
	!$limit && ($limit = 10);

	if (!in_array('id', $display)) {
		$display[] = 'id';
	}

	$conds_val = array();
	$result = array();

	foreach ($filter as $attr) {
		if (\System\Model\Database::attr_exists($model, $attr)) {
			$conds_val[] = "`".$attr."` LIKE '%".$value."%'";
		}
	}

	$conds[] = $conds_val;

	$objects = get_all($model)
		->distinct()
		->has($has)
		->where($conds)
		->paginate($limit)
		->fetch();


	foreach ($objects as $object) {
		$target = &$result[];
		$target = array();

		foreach (array('display', 'fetch') as $var) {
			foreach ($$var as $attr) {
				if (!$object->has_attr($attr) && method_exists($object, $attr)) {
					$target[$attr] = $object->$attr();
				} if (is_object($object->$attr) && method_exists($object->$attr, 'to_json')) {
					$target[$attr] = $object->$attr->to_json();
				} else {
					$target[$attr] = $object->$attr;
				}
			}
		}
	}
} else {
	$result = array();
}

$this->template('system/common', array("json_data" => $result));
