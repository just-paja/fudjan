<?

namespace System\Error
{
	class Database extends \System\Error
	{
		function __construct()
		{
			$d = func_get_args();
			if (strpos(strtolower($d[0]), 'duplicate') !== false || (isset($d[1]) && strpos(strtolower($d[1]), 'duplicate') !== false)) {
				$e = 'Cannot insert data because of duplicate unique key!';
			} elseif (strpos(strtolower($d[0]), 'syntax') !== false) {
				$e = 'Cannot run query because of syntax error.';
			} else {
				$e = 'Unhandled error';
			}

			parent::__construct('stack', $e, $d);
		}
	}
}
