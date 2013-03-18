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
				$e = 'Cannot insert data because of duplicate unique key.';
			} elseif (strpos($msg, 'syntax') !== false) {
				$e = 'Cannot run query because of syntax error.';
			} elseif (strpos($msg, 'table') !== false && strpos($msg, 'exist')) {
				$e = 'Table does not exist.';
			} else {
				$e = 'Unhandled database error';
			}

			parent::__construct($e, $d);
		}
	}
}
