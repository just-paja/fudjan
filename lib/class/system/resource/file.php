<?php

namespace System\Resource
{
	class File extends \System\Resource
	{
		const CLASS_FILE = '\\System\\File';

		const DIR_CACHE    = '/var/cache/static';
		const DIR_STATIC   = '/share';

		protected $file_path;
		protected $file_point;


		public function resolve()
		{
			if ($this->src == 'static') {
				if ($this->use_cache) {
					$file = BASE_DIR.$this::DIR_CACHE.DIRECTORY_SEPARATOR.$this->name_full;
				} else {
					$file = \System\Composer::resolve($this::DIR_STATIC.DIRECTORY_SEPARATOR.$this->name_full);
				}
			} else {
				$file = $this::DIR_MEDIA.DIRECTORY_SEPARATOR.$this->name_full;
			}

			if (any($file)) {
				$cname = $this::CLASS_FILE;

				$this->file_path  = $file;
				$this->file_point = $cname::from_path($file);

				if ($this->file_point) {
					$this->exists = $this->file_point->exists();
				} else {
					$this->exists = false;
				}
			} else {
				$this->exists = false;
			}
		}


		public function read()
		{
			$this->file_point->read_meta();
			$this->mime = $this->file_point->mime;
			$this->content = $this->file_point->get_content();
		}
	}
}
