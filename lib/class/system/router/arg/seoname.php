<?

namespace System\Router\Arg
{
	class SEONAME
	{
		const PATTERN = '.+\-[0-9]+';

		public function get_val()
		{
			$val = explode('-', $this->val);

			return int($val[count($val)-1]);
		}
	}
}
