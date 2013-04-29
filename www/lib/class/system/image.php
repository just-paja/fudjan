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
	class Image extends Model\Attr
	{
		const DIR = '/var/images';
		const DIR_TMP = '/var/tmp';
		const DIR_THUMBS = '/var/thumbs';
		const FILE_BAD_THUMB = '/share/pixmaps/pwf/bad_thumb.jpg';
		const IMG_JPEG_OLD = 3;

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

		/** Image attributes */
		static protected $attrs = array(
			"width"         => array('int'),
			"height"        => array('int'),
			"file_size"     => array('int'),
			"file_path"     => array('varchar'),
			"file_name"     => array('varchar'),
			"file_hash"     => array('varchar'),
			"format"        => array('varchar'),
			"src"           => array('varchar'),
			"file"          => array('varchar'),
			"tmp"           => array('bool'),
			"bad"           => array('bool'),
			"cache"         => array('bool'),
			"allow_save"    => array('bool'),
			"to_be_deleted" => array('bool'),
		);


		/** Read image dimensions from image file
		 * @return void
		 */
		private function read_dimensions()
		{
			if (!$this->width || !$this->height || !$this->format || !$this->file_size) {

				if (file_exists($p = $this->get_path(true))) {
					$this->file_size = filesize($p);
				}

				if (($info = self::get_image_size($this->get_path(true))) !== false && $info[0] !== false) {
					$this->width  = $info[0];
					$this->height = $info[1];
					$this->format = $info[2];
				}
			}
		}


		/** Get size of image in format %dx%d
		 * @return string
		 */
		public function get_size()
		{
			$this->read_dimensions();
			return $this->width.'x'.$this->height;
		}


		/** Get image format
		 * @return int
		 */
		public function get_format()
		{
			$this->read_dimensions();
			return $this->format;
		}


		/** Is valid image format
		 * @return bool
		 */
		public function is_image()
		{
			return !is_null($f = $this->get_format()) && $f;
		}


		/** Get image thumb url
		 * @param int  $width  Desired width
		 * @param int  $height Desired height
		 * @param bool $crop   Crop image if it does not fit (width, height):original(width, height) ratio
		 * @return string
		 */
		public function thumb($width = null, $height = null, $crop = true, $transparent = false)
		{
			if (is_null($width) && is_null($height)) {
				throw new \System\Error\Argument('You must pass width or height to \System\Image::thumb.');
			}


			if ($this->check_thumb($width, $height, $crop, true, $transparent)) {
				return $this->get_thumb_path($width, $height, $crop, $transparent);
			} else {
				if ($this->bad) {
					throw new \System\Error('Cannot generate thumb.');
				} else return self::gen_bad_thumb($width, $height);
			}
		}


		public function thumb_trans($width = null, $height = null, $crop = true)
		{
			return $this->thumb($width, $height, $crop, true);
		}


		/** Check if thumb already exists
		 * @param int  $width  Desired width
		 * @param int  $height Desired height
		 * @param bool $crop   Crop image if it does not fit (width, height):original(width, height) ratio
		 * @param bool $gen    Generate image if it does not exist
		 * @return bool
		 */
		private function check_thumb($width = null, $height = null, $crop = true, $gen = true, $transparent = false)
		{
			return
				file_exists(ROOT.$this->get_thumb_path($width, $height, $crop, $transparent)) ||
				($gen && $this->make_thumb($width, $height, $crop, $transparent));
		}


		/** Get path of thumb according to size
		 * @param int  $width  Desired width
		 * @param int  $height Desired height
		 * @param bool $crop   Crop image if it does not fit (width, height):original(width, height) ratio
		 * @return string
		 */
		private function get_thumb_path($width = null, $height = null, $crop = true, $transparent = false)
		{
			$name = $this->get_file_hash();
			return self::DIR_THUMBS.'/'.$width.'x'.$height.'/'.substr($name, 0, 5).'/'.$name.($crop ? '-crop':'').($transparent ? '.png':'.jpg');
		}


		/** Get md5 sum of file content
		 * @return string
		 */
		private function get_file_hash()
		{
			if (!$this->file_hash && file_exists($this->get_path(true))) {
			 $this->file_hash = md5(\System\File::read($this->get_path(true)));
		 }

			return $this->file_hash;
		}


		/** Create miniature of image
		 * @param int  $width  Desired width
		 * @param int  $height Desired height
		 * @param bool $crop   Crop image if it does not fit (width, height):original(width, height) ratio
		 * return bool
		 */
		private function make_thumb($width, $height, $crop = true, $transparent = false)
		{
			if (extension_loaded('imagemagick')) {
				$im = new ImageMagick($this->get_path(true));
				$im->resampleImage($width, $height);
				return $im->writeImage(ROOT.$this->get_thumb_path($width, $height, $crop));
			} else {
				return self::gen_thumb($this, $width, $height, $crop, $transparent);
			}
		}


		/** Get size of image
		 * @param string $path
		 * @return array
		 */
		public static function get_image_size($path)
		{
			return (array) @getimagesize($path);
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
			if (file_exists($path)|| file_exists($path = ROOT.$path)) {
				return new self(array(
					"src" => 'copy',
					"tmp_name"  => $path,
					"file_path" => str_replace(ROOT, '', $path),
					"file_name" => basename($path),
					"tmp" => false,
				));
			}

			return false;
		}


		/** Create new empty image
		 * @return self
		 */
		public static function from_scratch()
		{
			return new Image(array());
		}


		/** Export image object to JSON
		 * @param bool $encode Return encoded string
		 * @return string
		 */
		public function to_json($encode = true)
		{
			$data = array(
				"file_path" => $this->get_path(),
				"file_name" => $this->file_name,
				"file_size" => $this->file_size,
				"file_hash" => $this->get_file_hash(),
				"width"     => $this->width,
				"height"    => $this->height,
			);

			return $encode ? json_encode($data):$data;
		}


		/** Save image
		 * @param string $path
		 * @return bool
		 */
		public function save($path = null)
		{
			if ($this->cache && $this->allow_save) {
				if (!$path) {
					$new_name = $this->gen_name();
					$dir  = ROOT.self::DIR.'/'.substr($new_name, 0, 4);
					$path = $dir.'/'.$new_name;
					\System\Directory::check($dir);
				}

				\System\Directory::check(dirname($this->get_path(true)));
				if (($this->src == 'copy' && $ok = copy($this->get_path(true), $path)) || $ok = rename($this->get_path(true), $path)) {
					$this->file_path = $path;
					$this->file_name = basename($this->get_path(true));
					$this->cache = false;
					$this->tmp = false;
					$this->allow_save = false;
					chmod($this->file_path, 0644);
				}

				return $ok;
			}
		}


		/** Generate image name from sum of image head and other attributes
		 * @return string
		 */
		private function gen_name()
		{
			return md5(\System\File::read($this->get_path(true), false, NULL, -1, 2048).'-'.intval($this->file_size).'-'.intval($this->width).'x'.intval($this->height)).'.'.self::get_suffix($this->format);
		}


		/** Get suffix for format
		 * @param int $gd_format
		 * @return string Format suffix
		 */
		public static function get_suffix($gd_format)
		{
			if (isset(self::$gd_formats[$gd_format])) {
				return self::$gd_formats[$gd_format];
			} else throw new \System\Error\Argument('Unknown image format.');
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


		/** Generate thumb using GD library
		 * @param self $obj         Instance of image
		 * @param int  $w_new       Width
		 * @param int  $h_new       Height
		 * @param bool $crop        Crop
		 * @param bool $transparent Keep image transparency
		 * @return bool
		 */
		public static function gen_thumb(self $obj, $w_new, $h_new, $crop = true, $transparent = false)
		{
			$path = $obj->get_path(true);

			if (($w_new && !is_numeric($w_new)) || ($h_new && !is_numeric($h_new))) {
				throw new \System\Error\Argument("Width and height must be integer.");
			}

			if ($path != ROOT && file_exists($path)) {

				$obj->read_dimensions();
				$w_org = intval($obj->width);
				$h_org = intval($obj->height);

				$tpth = ROOT.$obj->get_thumb_path($w_new, $h_new, $crop, $transparent);
				\System\Directory::check(dirname($tpth));

				if ($w_new < $w_org || $h_new < $h_org) {
					list($w_new, $h_new, $xw, $xh, $dst_x, $dst_y) = self::calc_thumb_coords($w_org, $h_org, $w_new, $h_new, $crop);

					$im = self::get_image_resource($path, $obj->get_format());
					$th = imagecreatetruecolor($w_new, $h_new);

					if (!$transparent) {
						$wh = imagecolorallocate($th, 255, 255, 255);
						imagefill($th, 0, 0, $wh);
					} else {
						$transparent = imagecolorallocatealpha($th, 0, 0, 0, 127);
						imagefill($th, 0, 0, $transparent);
					}

					imagecopyresampled($th, $im, intval($dst_x), intval($dst_y), 0, 0, intval($xw), intval($xh), $w_org, $h_org);

					if (file_exists($tpth)) {
						unlink($tpth);
					}

					if ($transparent) {
						imagealphablending($th, false);
						imagesavealpha($th, true);
						return imagepng($th, $tpth);
					} else {
						return imagejpeg($th, $tpth, 99);
					}

					imagedestroy($th);
				} else {
					return copy($path, $tpth);
				}
			}

			return false;
		}


		/** Get image GD resource
		 * @param string $path   Path to image
		 * @param int    $format GD format constant
		 * @return resource
		 */
		public static function get_image_resource($path, $format)
		{
			$im = null;

			switch ($format) {
				case 1: $im = imagecreatefromgif($path); break;
				case 2: $im = imagecreatefromjpeg($path); break;
				case 3: $im = imagecreatefrompng($path); break;
			}

			if (!is_resource($im)) {
				throw new \System\Error\File(sprintf('Failed to open image "%s". File is not readable or format "%s" is not supported.', $path, self::get_suffix($format)));
			}

			return $im;
		}


		/** Calculate target thumb size and coordinates
		 * @param int  $w_org Original width
		 * @param int  $h_org Original height
		 * @param int  $w_new Desired width
		 * @param int  $h_new Desired height
		 * @param bool $crop  Crop image or change proportion
		 * @return array
		 */
		public static function calc_thumb_coords($w_org, $h_org, $w_new=null, $h_new=null, $crop=false)
		{
			$refit = false;

			if ($w_new <= 0 && $h_new) {
				$w_new = round(($w_org * $h_new) / $h_org);
				$refit = true;
			}

			if ($h_new <= 0 && $w_new) {
				$h_new = round(($h_org * $w_new) / $w_org);
				$refit = true;
			}

			if ($crop && !$refit) {
				if ($w_org / $h_org < $w_new / $h_new) {
					$xw = $w_new;
					$xh = round($h_org / $w_org * $w_new);
					$dst_x = 0;
					$dst_y = round(($xh > $h_new) ? (-1 * abs($h_new - $xh) / 2):(abs($h_new - $xh) / 2));
				} else {
					$xh = $h_new;
					$xw = round($w_org / $h_org * $h_new);
					$dst_x = round(($xw > $w_new) ? (-1 * abs($w_new - $xw) / 2):(abs($w_new - $xw) / 2));
					$dst_y = 0;
				}
			} else {
				$xw = $w_new;
				$xh = $h_new;
				$dst_x = $dst_y = 0;
			}

			return array($w_new, $h_new, $xw, $xh, $dst_x, $dst_y);
		}


		/** Can image be saved?
		 * @return bool
		 */
		public function allow_save()
		{
			return $this->allow_save;
		}


		/** Is image supposed to be deleted?
		 * @return bool
		 */
		public function is_to_be_deleted()
		{
			return $this->to_be_deleted;
		}


		/** Get path of image
		 * @param bool $with_root Include root in path of image
		 * @return bool
		 */
		public function get_path($with_root = false)
		{
			$path = str_replace(ROOT, '', $this->file_path);
			return $with_root ? ($this->tmp && !$this->bad ? '':ROOT).$path:$path;
		}


		/** Update attributes
		 * @param array $dataray
		 * @return $this
		 */
		public function update_attrs(array $dataray)
		{
			parent::update_attrs($dataray);

			!isset($dataray['src']) && $dataray['src'] = '';
			$this->allow_save = $this->src == 'upload' || $this->src == 'copy' || $this->src == 'migration';

			if (!($this->to_be_deleted = $dataray['src'] == 'none')) {
				if (isset($dataray['tmp_name']) && empty($dataray['error'])) {
					$this->tmp = $dataray['src'] == 'upload';

					if (is_uploaded_file($dataray['tmp_name']) || file_exists($dataray['tmp_name'])) {
						$this->file_path = $dataray['tmp_name'];
						$this->file_name = basename($this->get_path(true));
						$this->read_dimensions();
					} else throw new \System\Error\File(sprintf('Image "%s" could not be saved!', $dataray['tmp_name']));
				}
			}

			return $this;
		}


		/** Cache uploaded image in filesystem
		 * @return $this
		 */
		public function cache()
		{
			if ($this->is_image()) {
				$tmp_path = ROOT.self::DIR_TMP.'/'.$this->get_file_hash().'.'.self::get_suffix($this->get_format());
				if (@copy($this->get_path(true), $tmp_path)) {
					$this->file_path = str_replace(ROOT, '', $tmp_path);
					$this->tmp = false;
					$this->cache = true;
				} else throw new \System\Error\File(
					sprintf('Copying image from "%s" to "%s" failed while caching!', $this->get_path(true), $tmp_path),
					'Please check your permissions and disk space'
				);
			}

			return $this;
		}


		public function to_html($w = self::WIDTH_DEFAULT, $h = null, $crop = true, $transparent = null)
		{
			$path = ((is_null($transparent) || $transparent) && $this->get_format() == 3) ? $this->thumb_trans($w, $h, $crop):$this->thumb($w, $h, $crop);
			return \Stag::img(array("src" => $path, "alt" => ''));
		}
	}
}
