<?

namespace System
{
	class Page extends Model\Attr
	{
		private static $path = array();
		private static $input = array();
		private static $current;
		protected static $attrs = array(
			"title"     => array('varchar'),
			"page"      => array('varchar'),
			"path"      => array('varchar'),
			"seoname"   => array('varchar'),
			"modules"   => array('list'),
			"layout"    => array('list'),
			"variable"  => array('list'),
			"post"      => array('varchar'),
			"keywords"  => array('varchar'),
			"desc"      => array('text'),
			"robots"    => array('varchar'),
			"copyright" => array('varchar'),
			"author"    => array('varchar'),
			"no_debug"  => array('bool'),
			"request"   => array('object', "model" => '\System\Http\Request'),
		);


		public function __construct(array $dataray)
		{
			parent::__construct($dataray);

			if (strpos($this->path, '/cron') === 0) {
				$this->layout = array(null);
			}
		}


		/** Get page metadata
		 * @return array
		 */
		public function get_meta()
		{
			$dataray = array();
			$meta = cfg('output', 'meta_tags');

			foreach ((array) $meta as $name) {
				if (!empty($this->data[$name])) $dataray[$name] = array("name" => $name, "content" =>$this->data[$name]);
			}

			return $dataray;
		}


		/** Is the page readable?
		 * @return bool
		 */
		public function is_readable()
		{
			if (!$this->request->user()->is_root() && !empty($this->opts['groups'])) {
				foreach ($this->request->user()->get_group_ids() as $id) {
					if (in_array($id, $this->opts['groups'])) return true;
				}
				return false;
			}

			return true;
		}
	}
}
