<?

namespace System
{
	class Gps
	{
		private $data = array("lat" => null, "lng" => null);


		public function lat($val = null)
		{
			!is_null($val) && $this->data['lat'] = floatval($val);
			return $this->data['lat'];
		}


		public function lng($val = null)
		{
			!is_null($val) && $this->data['lng'] = floatval($val);
			return $this->data['lng'];
		}


		public function to_json()
		{
			return json_encode($this->get_data());
		}


		public function to_sql()
		{
			$data = array($this->lat(), $this->lng());
			return 'GeomFromText(\'POINT('.\System\Database::escape($data[0]).' '.\System\Database::escape($data[1]).')\')';
		}


		public function get_data()
		{
			return $this->data;
		}


		public static function from_latlng($lat, $lng)
		{
			return  self::from_array(array("lat" => $lat, "lng" => $lng));
		}


		public static function from_json($str)
		{
			return self::from_array(json_decode($str, true));
		}


		public static function from_sql($str)
		{
			$str = substr($str, strlen('POINT('));
			$str = substr($str, 0, strlen($str) - 1);
			$str = explode(' ', $str);
			return self::from_array(array("lat" => $str[0], "lng" => $str[1]));
		}


		public static function from_array(array $data)
		{
			$item = new self();
			$item->lat($data['lat']);
			$item->lng($data['lng']);
			return $item;
		}
	}
}
