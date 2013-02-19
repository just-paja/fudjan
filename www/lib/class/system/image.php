<?

namespace System
{
	class Image extends Model\Attr
	{
		const DIR = '/var/images';
		const DIR_TMP = '/var/tmp';
		const DIR_THUMBS = '/var/thumbs';
		const FILE_BAD_THUMB = '/share/pixmaps/pwf/bad_thumb.jpg';
		const IMG_JPEG_OLD = 3;

		private static $bad_thumb = null;

		public static $gd_formats = array(
			IMG_GIF  => "gif",
			IMG_JPG  => "jpg",
			self::IMG_JPEG_OLD => "jpg",
			IMG_PNG  => "png",
		);

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


		public function __get($attr)
		{
			if ($attr == 'width' || $attr == 'height') {
				empty($this->data[$attr]) && $this->read_dimensions();
			}

			if ($attr == 'file_size') {
				empty($this->data['file_size']) && ($this->file_size = filesize($this->get_path(true)));
			}

			return parent::__get($attr);
		}


		private function read_dimensions()
		{
			if (($info = self::get_image_size($this->get_path(true))) !== false && $info[0] !== false) {
				$this->width  = $info[0];
				$this->height = $info[1];
				$this->format = $info[2];
			}
		}


		public function get_size()
		{
			return $this->width.'x'.$this->height;
		}


		public function get_format()
		{
			$this->read_dimensions();
			return $this->format;
		}


		public function is_image()
		{
			return !is_null($this->get_format());
		}


		public function thumb($width, $height = null, $crop = true)
		{
			if ($this->check_thumb($width, $height, $crop)) {
				return $this->get_thumb_path($width, $height, $crop);
			} else {
				if ($this->bad) {
					throw new \System\Error('Cannot generate thumb.');
				} else return self::gen_bad_thumb($width, $height);
			}
		}


		private function check_thumb($width = null, $height = null, $crop = true, $gen = true)
		{
			return
				file_exists(ROOT.$this->get_thumb_path($width, $height)) ||
				($gen && $this->make_thumb($width, $height, $crop));
		}


		private function get_thumb_path($width = null, $height = null, $crop = true)
		{
			$name = $this->get_file_hash();
			return self::DIR_THUMBS.'/'.$width.'x'.$height.'/'.substr($name, 0, 5).'/'.$name.($crop ? '-crop':'').'.jpg';
		}


		private function get_file_hash()
		{
			if (!$this->file_hash && file_exists($this->get_path(true))) {
			 $this->file_hash = md5(\System\File::read($this->get_path(true)));
		 }

			return $this->file_hash;
		}


		private function make_thumb($width, $height, $crop = true)
		{
			if (extension_loaded('imagemagick')) {
				$im = new ImageMagick($this->get_path(true));
				$im->resampleImage($width, $height);
				return $im->writeImage(ROOT.$this->get_thumb_path($width, $height, $crop));
			} else {
				return self::gen_thumb($this, $width, $height, $crop);
			}
		}


		public static function get_image_size($path)
		{
			return (array) @getimagesize($path);
		}


		public static function from_json($json)
		{
			return new self(json_decode($json, true));
		}


		// absolute path or http (not implemented)
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
		 */
		public static function from_scratch()
		{
			return new Image(array());
		}


		/** Export image object to JSON
		 * @return string
		 */
		public function to_json()
		{
			return json_encode(array(
				"file_path" => $this->get_path(),
				"file_name" => $this->file_name,
				"file_size" => $this->file_size,
				"file_hash" => $this->get_file_hash(),
				"width"     => $this->width,
				"height"    => $this->height,
			));
		}


		public function save($path = null)
		{
			if ($this->cache && $this->allow_save) {
				if (!$path) {
					$new_name = $this->gen_name();
					$path = self::prepare_image_dir(ROOT.self::DIR.'/'.substr($new_name, 0, 4).'/'.$new_name);
				}

				self::prepare_image_dir($this->get_path(true));
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


		private function gen_name()
		{
			return md5(\System\File::read($this->get_path(true), false, NULL, -1, 2048).'-'.intval($this->file_size).'-'.intval($this->width).'x'.intval($this->height)).'.'.self::get_suffix($this->format);
		}


		public static function get_suffix($gd_format)
		{
			return self::$gd_formats[$gd_format];
		}


		private static function gen_bad_thumb($width = null, $height = null)
		{
			if (is_null(self::$bad_thumb)) {
				self::$bad_thumb = self::from_path(ROOT.self::FILE_BAD_THUMB);
				self::$bad_thumb->tmp = true;
				self::$bad_thumb->bad = true;
			}

			return self::$bad_thumb->thumb($width, $height);
		}


		private static function prepare_image_dir($path)
		{
			$p = dirname($path);
			if (!is_dir($p) && (strpos($path, self::DIR_THUMBS) !== false || strpos($path, self::DIR) !== false)) {
				$p = str_replace(ROOT, "", $p);
				$p = array_filter(explode('/', $p));
				$ip = ROOT.'/';

				foreach ($p as $dir) {
					if (is_dir($dp = $ip.$dir) || mkdir($dp)) {
						$ip .= $dir.'/';
					} else {
						break;
					}
				}
			}
			return $path;
		}


		public static function gen_thumb(self $obj, $w, $h, $crop = true)
		{
			$path = $obj->get_path(true);

			if (($w && !is_numeric($w)) || ($h && !is_numeric($h))) {
				throw new \System\Error\Argument("Width and height must be integer.");
			}

			if ($path != ROOT && file_exists($path)) {

				$f = false;
				$org_w = intval($obj->width);
				$org_h = intval($obj->height);

				// Prevent bad image size
				if ($bad_size = ($org_w == 0 && $org_h == 0)) {
					$obj->read_dimensions();
				}

				$tpth = self::prepare_image_dir(ROOT.$obj->get_thumb_path($w, $h, $crop));

				if (!$w && $h) {
					$w = round(($org_w * $h) / $org_h);
					$f = true;
				}

				if (!$h && $w) {
					$h = round(($org_h * $w) / $org_w);
					$f = true;
				}

				if ($w < $org_w || $h < $org_h) {

					switch ($obj->get_format()) {
						case 1:
							$im = imagecreatefromgif($path);
							break;
						case 2:
							$im = imagecreatefromjpeg($path);
							break;
						case 3:
							$im = imagecreatefrompng($path);
							break;
					}

					if ($crop && !$f) {
						if ($org_w / $org_h < $w / $h) {
							$xw = $w;
							$xh = round($org_h / $org_w * $w);
							$dst_x = 0;
							$dst_y = round(($xh > $h) ? (-1 * abs($h - $xh) / 2):(abs($h - $xh) / 2));
						} else {
							$xh = $h;
							$xw = round($org_w / $org_h * $h);
							$dst_x = round(($xw > $w) ? (-1 * abs($w - $xw) / 2):(abs($w - $xw) / 2));
							$dst_y = 0;
						}
					} else {
						$xw = $w;
						$xh = $h;
						$dst_x = $dst_y = 0;
					}

					$th = imagecreatetruecolor($w, $h);
					$wh = imagecolorallocate($th, 255, 255, 255);
					imagefill($th, 0, 0, $wh);
					imagecopyresampled($th, $im, intval($dst_x), intval($dst_y), 0, 0, intval($xw), intval($xh), $org_w, $org_h);

					if (file_exists($tpth)) {
						unlink($tpth);
					}

					return imagejpeg($th, $tpth, 99);
				} else {
					return copy($path, $tpth);
				}
			}

			return false;
		}


		public function allow_save()
		{
			return $this->allow_save;
		}


		public function is_to_be_deleted()
		{
			return $this->to_be_deleted;
		}


		public function get_path($with_root = false)
		{
			$path = str_replace(ROOT, '', $this->file_path);
			return $with_root ? ($this->tmp && !$this->bad ? '':ROOT).$path:$path;
		}


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


		public function cache()
		{
			$tmp_path = ROOT.self::DIR_TMP.'/'.$this->get_file_hash().'.'.self::get_suffix($this->get_format());
			if (@copy($this->get_path(true), $tmp_path)) {
				$this->file_path = str_replace(ROOT, '', $tmp_path);
				$this->tmp = false;
				$this->cache = true;
			} else throw new \System\Error\File(
				sprintf('Copying image from "%s" to "%s" failed while caching!', $this->get_path(true), $tmp_path),
				'Please check your permissions and disk space'
			);

			return $this;
		}
	}
}
