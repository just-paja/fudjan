<?

namespace System\Resource
{
	class Font extends \System\Resource\Generic
	{
		public function resolve()
		{
			$request = $res->request;
			$dir = self::FONTS_DIR.'/'.dirname($info['path']);
			$name = explode('.', basename($info['path']));
			$suffix = array_pop($name);
			$serial = array_pop($name);

			$name[] = $suffix;
			$name = implode('.', $name);
			$regex = '/^'.str_replace(array('.', '/'), array('\.', '\/'), $name).'$/';
			$files = \System\Composer::find($dir, $regex);

			if (any($files)) {
				$file = \System\File::from_path($files[0]);
				$file->read_meta()->load();
				self::set_headers($res, self::TYPE_FONT, $file->size());

				$res->mime = $file->mime;
				$res->set_content($file->get_content());
				$res->send();
				exit;
			}

			throw new \System\Error\NotFound();
		}
	}
}
