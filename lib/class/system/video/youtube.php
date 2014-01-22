<?

/** Youtube video integration
 * @package system
 * @subpackage media
 */
namespace System\Video
{
	/** Youtube video class
	 * @package system
	 * @subpackage media
	 */
	class Youtube
	{
		const URL_HOST  = 'youtube.com';
		const URL_WATCH = 'watch?v={id}';
		const URL_EMBED = 'embed/{id}';
		const URL_PARSE = '/^watch.*v\=([0-9a-zA-Z\-]+)/';
		const ID_PARSE  = '/^[0-9a-zA-Z\-]+$/';

		/** Youtube ID
		 * @param string
		 */
		private $id;


		/** Private constructor
		 * @param string $id
		 */
		private function __construct($id)
		{
			$this->id = $id;
		}


		/** Create instance from url
		 * @param string $url
		 * @return $this|false False on failure
		 */
		public static function from_url($url)
		{
			if (($s = strpos($url, self::URL_HOST)) !== false) {
				$url = substr($url, $s + strlen(self::URL_HOST) + 1);
				$matches = array();
				if (preg_match(self::URL_PARSE, $url, $matches) && isset($matches[1])) {
					return self::from_id($matches[1]);
				}
			}

			return false;
		}


		/** Create instance from id
		 * @param string $id
		 * @return $this|false False on failure
		 */
		public static function from_id($id)
		{
			if (preg_match(self::ID_PARSE, $id)) {
				return new self($id);
			}

			return false;
		}


		/** Get video data
		 * @return array
		 */
		public function get_data()
		{
			return array("id" => $this->id);
		}


		/** Get video URL
		 * @return string
		 */
		public function get_url()
		{
			return 'http://'.self::URL_HOST.'/'.stprintf(self::URL_WATCH, $this->get_data());
		}


		/** Get URL to embed iframe
		 * @return string
		 */
		public function get_embed_url()
		{
			return 'http://'.self::URL_HOST.'/'.stprintf(self::URL_EMBED, $this->get_data());
		}


		/** Get embed iframe
		 * @return string
		 */
		public function embed()
		{
			return \Tag::iframe(array(
				"class"           => 'video video_yt',
				"src"             => $this->get_embed_url(),
				"frameborder"     => 0,
				"allowfullscreen" => true,
				"output"          => false,
				"close"           => true,
			));
		}


		/** Convert youtube video to SQL
		 * @return string
		 */
		public function to_sql()
		{
			return $this->id;
		}
	}
}
