<?php

namespace System\Cache
{
	interface Ifce
	{
		public function store($path, $value, $ttl);
		public function fetch($path, &$var);
		public function get($path);
		public function release($path);
		public function flush();
	}
}
