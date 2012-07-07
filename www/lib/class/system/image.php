<?

namespace System
{
	class Image extends Model\Attr
	{
		const DIR = '/var/images';
		const DIR_TMP = '/var/tmp';
		const DIR_THUMBS = '/var/thumbs';

		private static $bad_thumb;
		public static $gd_formats = array(
			IMG_GIF  => "gif",
			IMG_JPG  => "jpg",
			IMG_JPEG => "jpg",
			IMG_PNG  => "png",
		);

		static protected $attrs = array(
			"int"      => array('width', 'height', 'file_size'),
			"string"   => array('file_path', 'file_name', 'file_hash', 'format', 'src', 'file'),
			"bool"     => array('tmp', 'allow_save', 'to_be_deleted'),
		);


		public function __get($attr)
		{
			if ($attr == 'width' || $attr == 'height') {
				!($this->data[$attr]) && $this->read_dimensions();
			}
			
			if ($attr == 'file_size') {
				!($this->data['file_size']) && ($this->file_size = filesize($this->get_path(true)));
			}

			return parent::__get($attr);
		}


		private function read_dimensions()
		{
			$info = self::get_image_size($this->get_path(true));
			$this->width  = $info[0];
			$this->height = $info[1];
			$this->format = $info[2];
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


		public function thumb($width = null, $height = null, $crop = true)
		{
			return ($this->check_thumb($width, $height, $crop)) ?
				$this->get_thumb_path($width, $height, $crop):
				self::gen_bad_thumb($width, $height);
		}


		private function check_thumb($width = null, $height = null, $crop = true)
		{
			return file_exists(ROOT.$this->get_thumb_path($width, $height)) ?
				true:
				$this->make_thumb($width, $height, $crop);
		}


		private function get_thumb_path($width = null, $height = null, $crop = true)
		{
			$name = $this->get_file_hash();
			return self::DIR_THUMBS.'/'.$width.'x'.$height.'/'.substr($name, 0, 5).'/'.$name.($crop ? '-crop':'').'.jpg';
		}


		private function get_file_hash()
		{
			if (!$this->file_hash) {
			 $this->file_hash = md5(@file_get_contents($this->get_path(true)));
		 }

			return $this->file_hash;
		}


		private function make_thumb($width = null, $height = null, $crop = true)
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
			if (file_exists($path)) {
				return new self(array(
					"src" => 'copy',
					"tmp_name" => $path
				));
			}
			return self::from_scratch();
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
			if ($this->tmp && $this->allow_save) {
				if (!$path) {
					$new_name = $this->gen_name();
					$path = self::prepare_image_dir(ROOT.self::DIR.'/'.substr($new_name, 0, 4).'/'.$new_name);
				}

				if (($this->src == 'copy' && $ok = copy($this->file_path, $path)) || $ok = rename($this->file_path, $path)) {
					$this->file_path = $path;
					$this->file_name = basename($path);
					chmod($this->file_path, 0644);
				}

				return $ok;
			}
		}


		private function gen_name()
		{
			return md5(file_get_contents($this->file_path, false, NULL, -1, 2048).'-'.intval($this->file_size).'-'.intval($this->width).'x'.intval($this->height)).'.'.self::get_suffix($this->format);
		}


		public static function get_suffix($gd_format)
		{
			return self::$gd_formats[$gd_format];
		}


		private static function gen_bad_thumb($width = null, $height = null)
		{
			if (!self::$bad_thumb) {
				self::$bad_thumb = new Image(array(
					"file_path" => '/share/pixmaps/evil-thumb.png',
					"file_name" => 'evil-thumb.png',
					"width"  => 512,
					"height" => 512
				));
			}

			return self::$bad_thumb->thumb($width, $height, false);
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


		public static function gen_thumb(Image $obj, $w = null, $h = null, $crop = true)
		{
			$path = $obj->get_path(true);

			if (($w && !is_numeric(	$w)) || ($w && !is_numeric($w))) {
				throw new CatchableException("Image::gen_thumb(): Width and height must be integer");
			}

			if ($path != ROOT && file_exists($path)) {
				$f = false;

				// Prevent bad image size
				if ($bad_size = ($org_w == 0 && $org_h == 0)) {
					$obj->read_dimensions();
				}

				$tpth = self::prepare_image_dir(ROOT.$obj->get_thumb_path($w, $h, $crop));
				$org_w = intval($obj->width);
				$org_h = intval($obj->height);

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
			return $with_root ? 
				($this->tmp ? $this->file_path:ROOT.$this->file_path):
				(strpos($this->file_path, ROOT) === 0 ?
					substr($this->file_path, strlen(ROOT)):
					$this->file_path);
		}


		public function update_attrs(array $dataray)
		{
			!isset($dataray['src']) && $dataray['src'] = '';
			$this->src = $dataray['src'];
			$this->allow_save = $this->src == 'upload' || $this->src == 'copy' || $this->src == 'migration';

			if (!($this->to_be_deleted = $dataray['src'] == 'none')) {
				if (isset($dataray['tmp_name']) && empty($dataray['error'])) {
					$this->tmp = $dataray['src'] == 'upload';
					if (is_uploaded_file($dataray['tmp_name']) || file_exists($dataray['tmp_name'])) {
						$this->file_path = $dataray['tmp_name'];
						$this->file_name = basename($dataray['tmp_name']);
						$this->read_dimensions();
					} else {
						message('error', _('Ukládání obrázku selhalo'));
					}
				} else {
					foreach ($dataray as $attr=>$value) {
						if (property_exists($this, $attr)) {
							$this->$attr = $value;
						}
					}
				}
			}
			
			return $this;
		}
	}
}