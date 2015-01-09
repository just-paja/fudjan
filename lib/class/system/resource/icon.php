<?

namespace System\Resource
{
	class Icon extends \System\Resource\Pixmap
	{
		const CLASS_FILE = '\\System\\Image';

		const DIR_CACHE    = '/var/cache/static/icons';
		const DIR_STATIC   = '/share/icons';


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
					$thumb->save();

					$this->file_path = BASE_DIR.$thumb->get_path();
					$this->file_point = $cname::from_path($this->file_path);
					$this->exists = $this->file_point->exists();
				}
			}
		}
	}
}
