<?

/** Database data model handling */
namespace System\Model
{
	abstract class Filter extends Database
	{
		public static function has_filter($name)
		{
			$cname = get_called_class();
			return method_exists($cname, 'filter_'.$name);
		}


		public static function filter(\System\Database\Query $query, $filter_name, $value)
		{
			$cname = get_called_class();

			if ($cname::has_filter($filter_name)) {
				$fname = 'filter_'.$filter_name;
				$cname::$fname($query, $value);
			} else throw new \System\Error\Argument('Filter was not found', $cname, $filter_name);
		}
	}
}
