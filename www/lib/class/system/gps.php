<?

namespace System
{
	class Gps
	{
		const GMAP_TYPE_ROADMAP   = 'roadmap';
		const GMAP_TYPE_SATELLITE = 'satellite';
		const GMAP_TYPE_HYBRID    = 'hybrid';
		const GMAP_TYPE_TERRAIN   = 'terrain';

		const MAP_WIDTH_DEFAULT = 200;
		const MAP_HEIGHT_DEFAULT = 200;


		private $data = array("lat" => null, "lng" => null);


		/** Get latitude
		 * @param float $val Set latitude to this val
		 * @returns float
		 */
		public function lat($val = null)
		{
			!is_null($val) && $this->data['lat'] = floatval($val);
			return $this->data['lat'];
		}


		/** Get longitude
		 * @param float $val Set longitude to this val
		 * @returns float
		 */
		public function lng($val = null)
		{
			!is_null($val) && $this->data['lng'] = floatval($val);
			return $this->data['lng'];
		}


		/* Get formatted latitude
		 * @returns string
		 */
		public function latf()
		{
			return $this->format($this->lat());
		}


		/* Get formatted longitude
		 * @returns string
		 */
		public function lngf()
		{
			return $this->format($this->lng());
		}


		/* Format float value
		 * @returns string
		 */
		private function format($num)
		{
			return number_format($num, 20, '.', '');
		}


		/* Get formatted gps coordinates
		 * @returns string
		 */
		public function gps()
		{
			return $this->latf().','.$this->lngf();
		}


		/* Convert object data to JSON
		 * @returns string
		 */
		public function to_json()
		{
			return json_encode($this->get_data());
		}


		/* Convert object data to SQL format
		 * @returns string
		 */
		public function to_sql()
		{
			$data = array($this->lat(), $this->lng());
			return 'GeomFromText(\'POINT('.\System\Database::escape($data[0]).' '.\System\Database::escape($data[1]).')\')';
		}


		/* Get object data
		 * @returns array
		 */
		public function get_data()
		{
			return $this->data;
		}


		/* Create object from separate coordinates
		 * @param float $lat
		 * @param float $lng
		 * @returns self
		 */
		public static function from_latlng($lat, $lng)
		{
			return  self::from_array(array("lat" => $lat, "lng" => $lng));
		}


		/* Create object from json data
		 * @param string $str
		 * @returns self
		 */
		public static function from_json($str)
		{
			return self::from_array(json_decode($str, true));
		}


		/* Create object from SQL format
		 * @param string $sql
		 * @returns self
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
		 * @returns self
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
		 * @returns string
		 */
		public function map_link()
		{
			return 'http://maps.google.com/maps?q='.$this->gps();
		}
	}
}
