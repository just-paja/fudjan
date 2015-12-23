<?php

namespace System\Cache\Driver
{
	class Apc implements \System\Cache\Ifce
	{
		public function store($path, $value, $ttl)
		{
			$res = apc_add($path, $value, $ttl);
			return $res ? $value:NULL;
		}


		public function fetch($path, &$var)
		{
			$res = false;
			$var = apc_fetch($path, $res);
			return $res ? $var:($var = NULL);
		}


		public function get($path)
		{
			$res = false;
			$var = apc_fetch($path, $res);
			return isset($res) ? $var:NULL;
		}


		public function release($path)
		{
			return apc_delete($path);
		}


		public function flush()
		{
			return apc_clear_cache('user');
		}
	}
}
