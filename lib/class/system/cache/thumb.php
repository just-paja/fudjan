<?

namespace System\Cache
{
	class Thumb extends \System\Model\Database
	{
		const DIR = '/var/cache/thumbs';

		protected static $attrs = array(
			"hash"       => array('varchar', 'is_unique' => true),
			"hash_image" => array('varchar', 'is_index' => true),
			"width"      => array('int',     'is_unsigned' => true, "is_null" => true),
			"height"     => array('int',     'is_unsigned' => true, "is_null" => true),
			"crop"       => array('bool'),
			"image"      => array('image'),
		);


		public function __get($attr)
		{
			if ($attr == 'hash' && !$this->hash) {
				$this->hash = $this->hash();
			}

			return parent::__get($attr);
		}


		public static function find_all_by_hash($hash)
		{
			return get_all('\System\Cache\Thumb')->where(array("hash_image" => $hash))->fetch();
		}


		public static function create_blank(array $attrs)
		{
			try {
				$path = cfg('thumbs', 'bad');
			} catch(\System\Error\Config $e) {
				$path = ROOT.'/share/pixmaps/pwf/bad_thumb.jpg';
			}

			$img = \System\Image::from_path($path);
			return self::from_image($img, $attrs);
		}


		public static function from_hash($hash)
		{
			return get_first('\System\Cache\Thumb')->where(array("hash" => $hash))->fetch();
		}


		public static function from_image(\System\Image $image, array $attrs)
		{
			if ($image->exists()) {
				$hash = self::create_hash($attrs, $image->hash(), $image->suffix());

				if (is_null($thumb = self::from_hash($hash))) {
					$thumb = new self($attrs);
					$thumb->image      = $image;
					$thumb->hash       = $thumb->hash();
					$thumb->hash_image = $image->hash();
				}

				return $thumb;
			}

			throw new \System\Error\File('Cannot create image thumb. Image does not exist.');
		}


		private static function create_hash($data, $img_hash, $img_suffix)
		{
			$data['img-hash']   = $img_hash;
			$data['img-suffix'] = $img_suffix;


			unset($data['image']);
			unset($data['created_at']);
			unset($data['updated_at']);
			unset($data['id_system_cache_thumb']);
			unset($data['hash']);
			unset($data['hash_image']);

			return md5(implode(':', $data));
		}


		public function hash()
		{
			return self::create_hash($this->get_data(), $this->image->hash(), $this->image->suffix());
		}


		public function drop()
		{
			if (\System\File::check($p = ROOT.$this->get_path())) {
				unlink($p);
			}

			return parent::drop();
		}


		public function url()
		{
			return '/share/thumb/'.\System\Resource::get_resource_list_wget_name('thumb', $this->hash(), $this->image->suffix());
		}


		public function check($gen = true)
		{
			$status = \System\File::check(ROOT.$this->get_path());

			if (!$status && $gen) {
				$this->gen();
				$status = $this->check(false);
			}

			return $status;
		}


		private function get_alternate_version()
		{
			$versions = $this->image->get_all_versions();

			if (any($versions)) {
				$this->image = array_pop($versions);
			}

			return $this;
		}


		public function gen()
		{
			if (!$this->image->exists()) {
				$this->get_alternate_version();
			}

			if ($this->image->exists()) {
				if (extension_loaded('imagemagick')) {
					$im = new ImageMagick($this->get_path(true));
					$im->resampleImage($this->width, $this->height);
					$im->writeImage(ROOT.$this->get_path($this->width, $this->height, $this->crop));
				} else {
					self::gen_gd($this);
				}
			} else throw new \System\Error\File('Cannot generate image thumb. It is not located on filesystem.', $this->image->get_path(), $this->get_path());

			return $this;
		}


		public function get_content()
		{
			return \System\File::read(ROOT.$this->get_path());
		}


		/** Generate thumb using GD library
		 * @param self $obj         Instance of image
		 * @return bool
		 */
		public static function gen_gd(self $obj)
		{
			$obj->image->refresh_info();
			$coords = array(
				'w_new' => $obj->width,
				'h_new' => $obj->height,
				'w_org' => intval($obj->image->width()),
				'h_org' => intval($obj->image->height()),
			);

			$tpth = ROOT.$obj->get_path();
			\System\Directory::check(dirname($tpth));

			if ($coords['w_new'] < $coords['w_org'] || $coords['h_new'] < $coords['h_org']) {
				$coords = self::calc_thumb_coords($coords, $obj->crop);

				$im = \System\Image::get_gd_resource($obj->image);
				$th = imagecreatetruecolor($coords['w_new'], $coords['h_new']);
				$trans = $obj->image->format() == 3;

				if ($trans) {
					$transparent = imagecolorallocatealpha($th, 0, 0, 0, 127);
					imagefill($th, 0, 0, $transparent);
				} else {
					$wh = imagecolorallocate($th, 255, 255, 255);
					imagefill($th, 0, 0, $wh);
				}

				imagecopyresampled($th, $im, intval($coords['dst_x']), intval($coords['dst_y']), 0, 0, intval($coords['xw']), intval($coords['xh']), $coords['w_org'], $coords['h_org']);

				if (file_exists($tpth)) {
					unlink($tpth);
				}

				if ($trans) {
					imagealphablending($th, false);
					imagesavealpha($th, true);
					imagepng($th, $tpth);
				} else {
					imagejpeg($th, $tpth, 99);
				}

				imagedestroy($th);
			} else {
				$obj->copy($tpth);
			}

			return $obj;
		}


		/** Get path of thumb
		 * @return string
		 */
		public function get_path()
		{
			$name = $this->image->hash();
			return self::DIR.'/'.$this->width.'x'.$this->height.'/'.substr($name, 0, 4).'/'.substr($name, 4, 4).'/'.substr($name, 8, 4).'/'.substr($name, 12).($this->crop ? '-crop':'').'.'.$this->image->suffix();
		}



		/** Calculate target thumb size and coordinates
		 * @param int  $coords['w_org'] Original width
		 * @param int  $coords['h_org'] Original height
		 * @param int  $coords['w_new'] Desired width
		 * @param int  $coords['h_new'] Desired height
		 * @param bool $crop  Crop image or change proportion
		 * @return array
		 */
		public static function calc_thumb_coords($coords, $crop=false)
		{
			$refit = false;

			if ($coords['w_new'] <= 0 && $coords['h_new']) {
				$coords['w_new'] = round(($coords['w_org'] * $coords['h_new']) / $coords['h_org']);
				$refit = true;
			}

			if ($coords['h_new'] <= 0 && $coords['w_new']) {
				$coords['h_new'] = round(($coords['h_org'] * $coords['w_new']) / $coords['w_org']);
				$refit = true;
			}

			if ($crop && !$refit) {
				if ($coords['w_org'] / $coords['h_org'] < $coords['w_new'] / $coords['h_new']) {
					$coords['xw'] = $coords['w_new'];
					$coords['xh'] = round($coords['h_org'] / $coords['w_org'] * $coords['w_new']);
					$coords['dst_x'] = 0;
					$coords['dst_y'] = round(($coords['xh'] > $coords['h_new']) ? (-1 * abs($coords['h_new'] - $coords['xh']) / 2):(abs($coords['h_new'] - $coords['xh']) / 2));
				} else {
					$coords['xh'] = $coords['h_new'];
					$coords['xw'] = round($coords['w_org'] / $coords['h_org'] * $coords['h_new']);
					$coords['dst_x'] = round(($coords['xw'] > $coords['w_new']) ? (-1 * abs($coords['w_new'] - $coords['xw']) / 2):(abs($coords['w_new'] - $coords['xw']) / 2));
					$coords['dst_y'] = 0;
				}
			} else {
				$coords['xw'] = $coords['w_new'];
				$coords['xh'] = $coords['h_new'];
				$coords['dst_x'] = $coords['dst_y'] = 0;
			}

			return $coords;
		}
	}
}
