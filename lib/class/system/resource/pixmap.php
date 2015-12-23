<?php

namespace System\Resource
{
	class Pixmap extends \System\Resource\File
	{
		const CLASS_FILE = '\\System\\Image';

		const DIR_CACHE    = '/var/cache/static/pixmaps';
		const DIR_STATIC   = '/share/pixmaps';


		public function resolve()
		{
			parent::resolve();

			if ($this->exists) {
				$rq = $this->request;

				if ($rq->get('width') || $rq->get('height')) {
					$thumb = \System\Cache\Thumb::from_image($this->file_point, array(
						'height' => $rq->get('height'),
						'width'  => $rq->get('width'),
					));

					$cname = self::CLASS_FILE;

					$thumb->crop = $rq->get('crop') == '' || !!$rq->get('crop');
					$thumb->check();

					if (!$thumb->id) {
						$thumb->save();
					}

					$this->file_path = BASE_DIR.$thumb->get_path();
					$this->file_point = $cname::from_path($this->file_path);
					$this->exists = true;
				}
			}
		}
	}
}
