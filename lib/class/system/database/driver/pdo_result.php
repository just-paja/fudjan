<?php

namespace System\Database\Driver
{
	class PdoResult extends \System\Database\Result
	{
		public function fetch()
		{
			return $this->first()->fetch_assoc();
		}


		public function fetch_assoc($key = null, $value = null)
		{
			$result = array();

			if ($this->res !== null) {
				while ($data = $this->res->fetch(\PDO::FETCH_ASSOC)) {
					$d = is_null($value) ? $data:$data[$value];

					if (is_null($key)) {
						$result[] = $d;
					} else {
						$result[$data[$key]] = $d;
					}

					if ($this->first) break;
				}
			}

			if ($this->first) {
				if (isset($result[0])) {
					$result = $result[0];
				} else {
					$result = false;
				}
			}
			return $result;
		}


		public function fetch_model($model, $key = null)
		{
			if (is_string($model)) {
				$result = array();

				if ($this->res !== null) {
					while ($data = $this->res->fetch(\PDO::FETCH_ASSOC)) {
						if (is_null($key)) {
							$result[] = new $model($data);
						} else {
							$result[$data[$key]] = new $model($data);
						}

						if ($this->first) break;
					}
				}

				$this->first && $result = $result[0];
				return $result;
			} else throw new \System\Error\Argument('Model name must be a string', $model);
		}
	}
}
