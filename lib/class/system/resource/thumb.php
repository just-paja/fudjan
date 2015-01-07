<?

namespace System\Resource
{
	class Thumb extends \System\Resource\File
	{
		const DIR_CACHE  = '/var/thumbs';
		const DIR_MEDIA  = '/var/thumbs';
		const DIR_STATIC = '/var/thumbs';


		public function resolve()
		{
			$hash = $this->name;

			if (!is_null($thumb = \System\Cache\Thumb::from_hash($hash))) {
				if ($thumb->check()) {
					$this->file_point = \System\Image::from_path(BASE_DIR.$thumb->get_path());
					$this->file_path  = $this->file_point->get_path();
					$this->exists     = $this->file_point->exists();
				} else throw new \System\Error\File('Failed to generate image thumb.');
			} else {
				$this->exists = false;
			}
		}
	}
}
