<?php

namespace System\Database\Driver
{
	class MysqliResult extends \System\Database\Result
	{
		public function fetch()
		{
			$result = array();

			if ($this->res !== null) {
				$result = $this->res->fetch_assoc();
			}

			$this->free && $this->res->free();
			return $result;
		}


		public function fetch_assoc($key = null, $value = null)
		{
			$result = array();

			if ($this->res !== null) {
				while ($data = $this->res->fetch_assoc()) {
					$d = is_null($value) ? $data:$data[$value];

					if (is_null($key)) {
						$result[] = $d;
					} else {
						$result[$data[$key]] = $d;
					}

					if ($this->first) break;
				}
			}

			$this->first && $result = $result[0];
			$this->free && $this->res->free();
			return $result;
		}


		public function fetch_model($model, $key = null)
		{
			if (!is_string($model)) throw new \System\Error\Argument('Model name must be a string', $model);
			$result = array();

			if ($this->res !== null) {
				while ($data = $this->res->fetch_assoc()) {
					if (is_null($key)) {
						$result[] = new $model($data);
					} else {
						$result[$data[$key]] = new $model($data);
					}
					if ($this->first) break;
				}
			}

			$this->first && $result = $result[0];
			$this->free && $this->res->free();
			return $result;
		}
	}
}
