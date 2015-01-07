<?

/** Image handling
 * @package system
 * @subpackage media
 */
namespace System
{
	/** Image handling class
	 * @package system
	 * @subpackage media
	 */
	class Image extends \System\File
	{
		const FILE_BAD_THUMB = '/share/pixmaps/pwf/bad_thumb.jpg';
		const IMG_JPEG_OLD = 3;
		const DIR_CACHE = '/var/cache';
		const RESOURCE_TYPE = 'pixmap';

		const WIDTH_DEFAULT = 100;
		const HEIGHT_DEFAULT = 100;

		/** Instance used for bad thumbs
		 * @param null|self
		 */
		private static $bad_thumb = null;

		/** Available image formats
		 * @param array
		 */
		public static $gd_formats = array(
			IMG_GIF  => "gif",
			IMG_JPG  => "jpg",
			self::IMG_JPEG_OLD => "jpg",
			IMG_PNG  => "png",
		);


		public static function fetch($url)
		{
			return self::from_file(parent::fetch($url));
		}


		public function width()
		{
			if (!$this->width) {
				$this->refresh_info();
			}

			return $this->width;
		}


		public function height()
		{
			if (!$this->height) {
				$this->refresh_info();
			}

			return $this->height;
		}


		public function format()
		{
			if (!$this->format) {
				$this->refresh_info();
			}

			return $this->format;
		}


		public function refresh_info()
		{
			if ($this->is_cached()) {
				$info = getimagesizefromstring($this->get_content());
			} else {
				if ($this->exists()) {
					$info = getimagesize($this->get_path());
				} else throw new \System\Error\File('Cannot read image size. It is not loaded nor in the filesystem.');
			}

			$this->width  = $info[0];
			$this->height = $info[1];
			$this->format = $info[2];
			return $this;
		}


		/** Get size of image in format %dx%d
		 * @return string
		 */
		public function get_size()
		{
			return $this->width().'x'.$this->height();
		}


		/** Is valid image format
		 * @return bool
		 */
		public function is_image()
		{
			return !is_null($f = $this->format()) && $f;
		}


		/** Get image thumb url
		 * @param int  $width  Desired width
		 * @param int  $height Desired height
		 * @param bool $crop   Crop image if it does not fit (width, height):original(width, height) ratio
		 * @return string
		 */
		public function thumb($width = null, $height = null, $crop = true)
		{
			if (is_null($width) && is_null($height)) {
				throw new \System\Error\Argument('You must pass width or height to \System\Image::thumb.');
			}

			$opts = array(
				"width"  => $width,
				"height" => $height,
				"crop"   => $crop,
			);

			try {
				$thumb = \System\Cache\Thumb::from_image($this, $opts);
			} catch(\System\Error\File $e) {
				$thumb = \System\Cache\Thumb::create_blank($opts);
			}

			if (!$thumb->id) {
				$thumb->save();
			}

			return $thumb->url();
		}


		private static function cache_thumb_info($hash, $data)
		{
			\System\File::put(ROOT.self::DIR_CACHE.'/'.$hash, json_encode($data));
		}


		public function get_thumb_resource_name($width = null, $height = null, $crop = null)
		{
			return $this->hash().(':'.$width.'x'.$height).($crop ? ':crop':':nocrop');
		}


		/** Check if thumb already exists
		 * @param int  $width  Desired width
		 * @param int  $height Desired height
		 * @param bool $crop   Crop image if it does not fit (width, height):original(width, height) ratio
		 * @param bool $gen    Generate image if it does not exist
		 * @return bool
		 */
		private function check_thumb($width = null, $height = null, $crop = true, $gen = true)
		{
			return
				file_exists(ROOT.$this->get_thumb_path($width, $height, $crop)) ||
				($gen && $this->make_thumb($width, $height, $crop));
		}



		/** Create image instance from JSON
		 * @param string $json
		 */
		public static function from_json($json)
		{
			return new self(\System\Json::decode($json));
		}


		/** Create image instance from path. Absolute path or http are not implemented
		 * @param string $path
		 * $return self|false False on failure
		 */
		public static function from_path($path)
		{
			$image = self::from_file(parent::from_path($path));
			$image->keep = true;
			return $image;
		}


		public static function from_tmp($path, $real_name)
		{
			$image = self::from_file(parent::from_tmp($path, $real_name));
			$image->keep = true;
			return $image;
		}


		public static function from_file(\System\File $file = null)
		{
			$img = null;

			if (!is_null($file)) {
				$img = new self($file->get_data());
				$file->keep = true;

				if ($file->is_cached()) {
					$img->set_content($file->get_content());
				}
			}

			return $img;
		}


		/** Generate bad thumbnail path
		 * @param int $width
		 * @param int $height
		 * @return string
		 */
		private static function gen_bad_thumb($width = null, $height = null)
		{
			if (is_null(self::$bad_thumb)) {
				self::$bad_thumb = self::from_path(ROOT.self::FILE_BAD_THUMB);
				self::$bad_thumb->tmp = true;
				self::$bad_thumb->bad = true;
			}

			return self::$bad_thumb->thumb($width, $height);
		}


		/** Get image GD resource
		 * @param string $path   Path to image
		 * @param int    $format GD format constant
		 * @return resource
		 */
		public static function get_gd_resource(self $img)
		{
			$im = null;

			switch ($img->format()) {
				case 1: $im = imagecreatefromgif($img->get_path()); break;
				case 2: $im = imagecreatefromjpeg($img->get_path()); break;
				case 3: $im = imagecreatefrompng($img->get_path()); break;
			}

			if (!is_resource($im)) {
				throw new \System\Error\File(sprintf('Failed to open image "%s". File is not readable or format "%s" is not supported.', $path, self::get_suffix($format)));
			}

			return $im;
		}


		public function to_html(\System\Template\Renderer $ren, $w = self::WIDTH_DEFAULT, $h = null, $crop = true)
		{
			return \Stag::img(array("src" => $this->thumb($w, $h, $crop), "alt" => ''));
		}


		public static function from_form($value)
		{
			return self::from_file(parent::from_form($value));
		}


		public function drop()
		{
			$this->clear_thumbs();
			return parent::drop();
		}


		public function clear_thumbs()
		{
			$thumbs = $this->find_all_thumbs();

			foreach ($thumbs as $thumb) {
				$thumb->drop();
			}

			return $this;
		}


		public function find_all_thumbs()
		{
			return \System\Cache\Thumb::find_all_by_hash($this->hash());
		}


		public function to_object()
		{
		}
	}
}
