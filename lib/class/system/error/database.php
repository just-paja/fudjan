<?

namespace System\Error
{
	class Database extends \System\Error
	{
		function __construct()
		{
			$d = func_get_args();
			$msg = strtolower($d[0]);

			if (strpos($msg, 'duplicate') !== false || (isset($d[1]) && strpos(strtolower($d[1]), 'duplicate') !== false)) {
				array_unshift($d, 'Cannot insert data because of duplicate unique key.');
			} elseif (strpos($msg, 'syntax') !== false) {
				array_unshift($d, 'Cannot run query because of syntax error.');
			} elseif (strpos($msg, 'table') !== false && strpos($msg, 'exist')) {
				array_unshift($d, 'Table does not exist.');
			} elseif (strpos($msg, 'unknown column') === 0) {
				array_unshift($d, 'Missing column');
			} else {
				array_unshift('Unhandled database error');
			}

			call_user_func_array(array('parent', '__construct'), $d);
		}
	}
}
