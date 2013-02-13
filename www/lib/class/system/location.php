<?

namespace System
{
	class Location extends \System\Model\Database
	{
		protected static $attrs = array(
			"name" => array("varchar"),
			"addr" => array("varchar"),
			"gps"  => array("point"),
			"desc" => array("text"),
			"site" => array("url"),
		);


		protected static $belongs_to = array(
			"user" => array("model" => '\System\User'),
		);


		/** Returns URL to the Google Static Maps
		 * @param int $w Width
		 * @param int $h Height
		 * @param string $type
		 * @returns string
		 */
		public function map($w = \System\Gps::MAP_WIDTH_DEFAULT, $h = \System\Gps::MAP_HEIGHT_DEFAULT, $type = \System\Gps::GMAP_TYPE_ROADMAP)
		{
			return 'http://maps.googleapis.com/maps/api/staticmap?sensor=false&amp;zoom=14&amp;size='.$w.'x'.$h.'&amp;maptype='.$type.'&amp;markers='.$this->gps->gps();
		}


		/** Get link to the Google Maps
		 * @returns string
		 */
		public function map_link()
		{
			return 'http://maps.google.com/maps?q='.$this->gps->gps();
		}


		/** Get HTML representation of location
		 * @param int $w Width
		 * @param int $h Height
		 * @param string $type
		 * @returns string
		 */
		public function map_html($w = \System\Gps::MAP_WIDTH_DEFAULT, $h = \System\Gps::MAP_HEIGHT_DEFAULT, $type = \System\Gps::GMAP_TYPE_ROADMAP)
		{
			return \Tag::a(array(
				"output"  => false,
				"class"   => 'location_map',
				"href"    => $this->map_link(),
				"content" => \Tag::img(array(
					"src"    => $this->map($w, $h, $type),
					"alt"    => sprintf(l('core_location_on_map'), $this->name),
					"output" => false,
				)),
			));
		}
	}
}
