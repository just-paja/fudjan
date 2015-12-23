<?php

namespace System\Cache\Driver
{
	class Runtime implements \System\Cache\Ifce
	{
		private $storage = array();

		public function store($path, $value, $ttl)
		{
			return $this->storage[$path] = $value;
		}


		public function fetch($path, &$var)
		{
			return $var = $this->get($path);
		}


		public function get($path)
		{
			return isset($this->storage[$path]) ? $this->storage[$path]:null;
		}


		public function release($path)
		{
			unset($this->storage[$path]);
		}


		public function flush()
		{
			$this->storage = array();
		}
	}
}
