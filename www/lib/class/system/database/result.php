<?

namespace System\Database
{
	abstract class Result
	{
		protected $res;
		protected $free  = true;
		protected $first = false;


		public function __construct($resource, $freeable = true)
		{
			$this->res  = $resource;
			$this->free = !!$freeable;
		}


		public function fetch()
		{
		}


		public function fetch_assoc($key = null, $value = null)
		{
		}


		public function fetch_model($model, $key = null)
		{
		}


		public function &nofree()
		{
			$this->free = false;
			return $this;
		}


		public function &first()
		{
			$this->first = true;
			return $this;
		}
	}
}
