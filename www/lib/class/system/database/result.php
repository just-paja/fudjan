<?

namespace System\Database
{
	class Result
	{
		private $res;
		private $free = true;
		private $first  = false;

		public function __construct($resource)
		{
			$this->res = is_object($resource) && get_class($resource) == 'mysqli_result' ? $resource:null;
		}


		public function fetch()
		{
			$result = array();

			if ($this->res !== null) {
				$result = $this->res->fetch_assoc();
			}

			$this->free && $this->res->free();
			return $result;
		}


		public function fetch_assoc()
		{
			$result = array();

			if ($this->res !== null) {
				while ($data = $this->res->fetch_assoc()) {
					$result[] = $data;
					if ($this->first) break;
				}
			}

			$this->first && $result = $result[0];
			$this->free && $this->res->free();
			return $result;
		}


		public function fetch_model($model)
		{
			if (!is_string($model)) throw new ArgumentException('Model name must be a string', $model);
			$result = array();

			if ($this->res !== null) {
				while ($data = $this->res->fetch_assoc()) {
					$result[] = new $model($data);
					if ($this->first) break;
				}
			}

			$this->first && $result = $result[0];
			$this->free && $this->res->free();
			return $result;
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
