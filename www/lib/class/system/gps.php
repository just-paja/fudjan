<?

namespace System
{
	class Gps extends \System\Model\Attr
	{
		const GMAP_TYPE_ROADMAP   = 'roadmap';
		const GMAP_TYPE_SATELLITE = 'satellite';
		const GMAP_TYPE_HYBRID    = 'hybrid';
		const GMAP_TYPE_TERRAIN   = 'terrain';

		const MAP_WIDTH_DEFAULT = 200;
		const MAP_HEIGHT_DEFAULT = 200;


		protected static $attrs = array(
			"lat" => array('float'),
			"lng" => array('float'),
		);


		/** Get latitude
		 * @param float $val Set latitude to this val
		 * @return float
		 */
		public function lat($val = null)
		{
			!is_null($val) && $this->data['lat'] = floatval($val);
			return $this->data['lat'];
		}


		/** Get longitude
		 * @param float $val Set longitude to this val
		 * @return float
		 */
		public function lng($val = null)
		{
			!is_null($val) && $this->data['lng'] = floatval($val);
			return $this->data['lng'];
		}


		/* Get formatted latitude
		 * @return string
		 */
		public function latf()
		{
			return $this->format($this->lat());
		}


		/* Get formatted longitude
		 * @return string
		 */
		public function lngf()
		{
			return $this->format($this->lng());
		}


		/* Format float value
		 * @return string
		 */
		private function format($num)
		{
			return number_format($num, 20, '.', '');
		}


		/* Get formatted gps coordinates
		 * @return string
		 */
		public function gps()
		{
			return $this->latf().','.$this->lngf();
		}


		/* Convert object data to JSON
		 * @param bool $encode Return encoded string
		 * @return string
		 */
		public function to_json($encode = true)
		{
			return $encode ? json_encode($this->get_data()):$this->get_data();
		}


		/* Convert object data to SQL format
		 * @return string
		 */
		public function to_sql()
		{
			$data = array($this->lat(), $this->lng());
			return 'GeomFromText(\'POINT('.\System\Database::escape($data[0]).' '.\System\Database::escape($data[1]).')\')';
		}


		/* Get object data
		 * @return array
		 */
		public function get_data()
		{
			return $this->data;
		}


		/* Create object from separate coordinates
		 * @param float $lat
		 * @param float $lng
		 * @return self
		 */
		public static function from_latlng($lat, $lng)
		{
			return  self::from_array(array("lat" => $lat, "lng" => $lng));
		}


		/* Create object from json data
		 * @param string $str
		 * @return self
		 */
		public static function from_json($str)
		{
			return self::from_array(json_decode($str, true));
		}


		/* Create object from SQL format
		 * @param string $sql
		 * @return self
		 */
		public static function from_sql($str)
		{
			$str = substr($str, strlen('POINT('));
			$str = substr($str, 0, strlen($str) - 1);
			$str = explode(' ', $str);
			return self::from_array(array("lat" => $str[0], "lng" => $str[1]));
		}


		/* Create object from data
		 * @param array $data
		 * @return self
		 */
		public static function from_array(array $data)
		{
			$item = new self();
			$item->lat($data['lat']);
			$item->lng($data['lng']);
			return $item;
		}


		/** Returns URL to the Google Static Maps
		 * @param int $w Width
		 * @param int $h Height
		 * @param string $type
		 */
		public function map($w = self::MAP_WIDTH_DEFAULT, $h = self::MAP_HEIGHT_DEFAULT, $type = self::GMAP_TYPE_ROADMAP)
		{
			return 'http://maps.googleapis.com/maps/api/staticmap?sensor=false&amp;size='.$w.'x'.$h.'&amp;maptype='.$type.'&amp;markers='.$this->gps();
		}


		/** Get link to the Google Maps
		 * @return string
		 */
		public function map_link()
		{
			return 'http://maps.google.com/maps?q='.$this->gps();
		}


		/** Convert gps coordinates to html
		 * @param int $w
		 * @param int $h
		 * @param int $type
		 * @return string
		 */
		public function to_html($w = \System\Gps::MAP_WIDTH_DEFAULT, $h = \System\Gps::MAP_HEIGHT_DEFAULT, $type = \System\Gps::GMAP_TYPE_ROADMAP)
		{
			return \Stag::img(array("src" => $this->map($w, $h, $type), "alt" => ''));
		}

	}
}
