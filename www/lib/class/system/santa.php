<?

/** Santa package handling
 * @package system
 * @subpackage santa
 */
namespace System
{
	/** Santa package handling
	 * @package system
	 * @subpackage santa
	 */
	abstract class Santa
	{
		const CACHE_MAX = 2592000;
		const DIR_TREE  = '/var/cache/santa';

		/** Loaded tree of santa packages
		 * @param array
		 * @todo Rewrite so that whole tree is not loaded into RAM
		 */
		static private $tree = array();


		/** Get list of all configured repositories
		 * @return array
		 */
		public static function get_repo_list()
		{
			return cfg('santa', 'repositories');
		}


		/** Sync from all configured repositories
		 * @return void
		 */
		public static function sync()
		{
			foreach (self::get_repo_list() as $repo=>$url) {
				self::sync_from($repo);
			}
		}


		/** Sync from repository
		 * @param string $repo Name of repository
		 * @return void
		 */
		public static function sync_from($repo)
		{
			$url  = cfg('santa', 'repositories', $repo);
			$data = \System\Offcom\Request::get($url);

			if ($data->ok()) {
				$tmp = \System\Json::decode($data->content, true);
				self::$tree[$repo] = $tmp['tree'];
				\System\Directory::check(ROOT.self::DIR_TREE);
				\System\File::put(self::get_repo_file($repo), json_encode(self::$tree[$repo]));
			} else throw new \System\Error\Connection(l('Fetching recent tree data failed'), sprintf(l('HTTP error %s '), $data->status));
		}


		/** Get target file for repository tree
		 * @param string $repo Name of repository
		 * @return string
		 */
		public static function get_repo_file($repo)
		{
			return ROOT.self::DIR_TREE.'/'.$repo.'.json';
		}


		/** Get whole package tree, load it if necessary
		 * @return array
		 */
		public static function get_tree()
		{
			empty(self::$tree) && self::load_tree();
			return self::$tree;
		}


		/** Load tree into memory
		 */
		public static function load_tree()
		{
			self::$tree = array();

			foreach (self::get_repo_list() as $repo=>$url) {
				self::$tree[$repo] = \System\Json::read(self::get_repo_file($repo));
			}
		}


		/** Get list of all packages and versions
		 * @return array
		 */
		public static function get_all()
		{
			$packages = array();
			$tree = self::get_tree();

			foreach ($tree as $repo => $pkg_list) {
				foreach ($pkg_list as $cname => $category) {
					foreach ($category as $package_name=>$pkg_data) {
						$str = $cname.'/'.$package_name;

						if (any($packages[$str])) {
							$pkg = &$packages[$str];
						} else {
							$pkg = new \System\Santa\Package(array(
								"name"      => $package_name,
								"repo"      => $repo,
								"category"  => $cname,
								"desc"      => $pkg_data['desc'],
								"homepage"  => isset($pkg_data['homepage']) ? $pkg_data['homepage']:'',
								"available" => array(),
							));
						}

						foreach ($pkg_data['versions'] as $ver) {
							$pkg->add_version($repo, $ver['name'], $ver['branch']);
						}

						$packages[$str] = $pkg;
					}
				}
			}

			return $packages;
		}

		/** Get list of all installed packages
		 * @return array
		 */
		public static function get_all_installed()
		{
			$packages = array();

			foreach (self::get_all() as $pkg) {
				if ($pkg->is_installed()) {
					$packages[] = $pkg;
				}
			}

			return $packages;
		}


		/** Get list of available updates
		 * @param null|string $branch Use this branch to install package from
		 * @return array
		 */
		public static function get_update_list()
		{
			$old = self::get_all_installed();
			$up = array();

			foreach ($old as $pkg) {
				if ($pkg->is_available_for_update()) {
					$up[] = $pkg;
				}
			}

			return $up;
		}


		/** Create package by passing path with metadata
		 * @param string
		 */
		public static function from_metadir($path)
		{
			if (is_dir($path) && file_exists($path.'/version')) {
				$category_tmp = explode('/', $path);
				array_pop($category_tmp);
				$cfg = explode("\n", \System\File::read($path.'/version'));
				$pkg = new self(array(
					'category'   => array_pop($category_tmp),
					'name_short' => $cfg[0],
					'desc'       => $cfg[1],
					'version'    => $cfg[2],
					'name'       => $cfg[3],
					'branch'     => any($cfg[4]) ? $cfg[4]:'stable',
				));

				return $pkg;
			} else throw new \System\Error\File(sprintf(l('Cannot load package metadata from directory "%s"'), $path));
		}


		/** Create package using only its' name
		 * @param string $name
		 */
		public static function from_name($name)
		{
			self::load_tree();
			$category = '';

			if (strpos($name, '/') > 0) {
				list($category, $name) = explode('/', $name, 2);
			}

			foreach (self::$tree as $branch) {
				if ($category && any($branch[$name])) {
					return new self($branch[$name]);
				} else {
					foreach ($branch as $category) {
						if (any($category[$name])) {
							return new self($category[$name]);
						}
					}
				}
			}

			return false;
		}


		/** Does package exist
		 * @param string $name
		 * @return bool
		 */
		public static function exists($name)
		{
			self::load_tree();
			$category = '';

			if (strpos($name, '/') > 0) {
				list($category, $name) = explode('/', $name, 2);
			}

			foreach (self::$tree as $branch) {
				if ($category && any($branch[$name])) {
					return true;
				} else {
					foreach ($branch as $category) {
						if (any($category[$name])) {
							return true;
						}
					}
				}
			}

			return false;
		}

		/** Get list of all installed files
		 * @return array
		 */
		public static function get_all_installed_files()
		{
			$packages = self::get_all_installed();
			$files = array();

			foreach ($packages as $pkg) {
				$files = array_merge($files, $pkg->get_file_manifest());
			}

			return $files;
		}



		/** Compare two versions
		 * @param string $a
		 * @param string $b
		 * @return bool|int 0 if equal
		 */
		public static function greater_version_than($a, $b)
		{
			$a = array_map('intval', explode('.', $a));
			$b = array_map('intval', explode('.', $b));

			foreach ($a as $num) {
				if ($a > $b) {
					return true;
				} elseif ($a < $b) {
					return false;
				}
			}

			return 0;
		}


		/** Check if cache dir exists
		 * @return bool false if not
		 */
		private static function check_tree_dir()
		{
			return is_dir(ROOT.self::DIR_TMP_TREE) ?
				true:mkdir(ROOT.self::DIR_TMP_TREE, 0775, true);
		}



		/** Get list of available package branches
		 * @return array set of branches
		 */
		public static function get_branch_list()
		{
			self::load_tree();
			return array_keys(self::$tree);
		}


	}
}
