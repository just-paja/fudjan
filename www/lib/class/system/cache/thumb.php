<?

namespace System\Cache
{
	class Thumb extends \System\Model\Database
	{
		const DIR = '/var/thumbs';

		protected static $attrs = array(
			"hash"   => array('varchar', 'is_unique' => true),
			"width"  => array('int',     'is_unsigned' => true, "is_null" => true),
			"height" => array('int',     'is_unsigned' => true, "is_null" => true),
			"crop"   => array('bool'),
			"image"  => array('image'),
		);


		public function __get($attr)
		{
			if ($attr == 'hash' && !$this->hash) {
				$this->hash = $this->hash();
			}

			return parent::__get($attr);
		}


		public static function from_hash($hash)
		{
			return get_first('\System\Cache\Thumb')->where(array("hash" => $hash))->fetch();
		}


		public static function from_image(\System\Image $image, array $attrs)
		{
			$hash = self::create_hash($attrs, $image->hash(), $image->suffix());

			if (is_null($thumb = self::from_hash($hash))) {
				$thumb = new self($attrs);
				$thumb->image = $image;
				$thumb->hash = $thumb->hash();
			}

			return $thumb;
		}


		private static function create_hash($data, $img_hash, $img_suffix)
		{
			$data['img-hash']   = explode('-', $img_hash);
			$data['img-suffix'] = $img_suffix;

			array_pop($data['img-hash']);
			$data['img-hash'] = implode('-', $data['img-hash']);

			unset($data['image']);
			unset($data['created_at']);
			unset($data['updated_at']);
			unset($data['id_system_cache_thumb']);
			unset($data['hash']);

			return md5(implode(':', $data));
		}


		public function hash()
		{
			return self::create_hash($this->get_data(), $this->image->hash(), $this->image->suffix());
		}


		public function url()
		{
			return '/share/thumb/'.\System\Resource::get_resource_list_wget_name('thumb', $this->hash(), $this->image->suffix());
		}


		public function check($gen = true)
		{
			if (($action = !\System\File::check($this->get_path())) && $gen) {
				$this->gen();
				return $this->check(false);
			}

			return $action;
		}


		public function gen()
		{
			if (extension_loaded('imagemagick')) {
				$im = new ImageMagick($this->get_path(true));
				$im->resampleImage($this->width, $this->height);
				$im->writeImage(ROOT.$this->get_path($this->width, $this->height, $this->crop));
			} else {
				self::gen_gd($this);
			}

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
			if ($obj->image->exists()) {
				$obj->image->refresh_info();

				$w_new = $obj->width;
				$h_new = $obj->height;
				$w_org = intval($obj->image->width());
				$h_org = intval($obj->image->width());

				$tpth = ROOT.$obj->get_path();
				\System\Directory::check(dirname($tpth));

				if ($w_new < $w_org || $h_new < $h_org) {
					list($w_new, $h_new, $xw, $xh, $dst_x, $dst_y) = self::calc_thumb_coords($w_org, $h_org, $w_new, $h_new, $obj->crop);

					$im = \System\Image::get_gd_resource($obj->image);
					$th = imagecreatetruecolor($w_new, $h_new);
					$trans = $obj->image->format() == 3;

					if ($trans) {
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
			} else throw new \System\Error\File('Cannot generate image thumb. It is not located on filesystem.', $obj->get_path());

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


	}
}
