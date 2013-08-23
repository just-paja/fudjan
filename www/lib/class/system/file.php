<?

/** File handling
 * @package system
 * @subpackage files
 */
namespace System
{
	/** File handling class
	 * @package system
	 * @subpackage files
	 * @property $attrs
	 */
	class File extends Model\Attr
	{
		const DIR = '/var/files';
		const TMP_DIR = '/var/tmp';
		const FETCHED_SIGN = '-FETCHED';
		const MOD_DEFAULT = 0664;


		protected $size;
		protected $data;


		/** File attrmodel attributes */
		static protected $attrs = array(
			"hash" => array('varchar'), // DB
			"path" => array('varchar'),
			"name" => array('varchar'),
			"mime" => array('varchar'),
			"suff" => array('varchar'), // DB
			"size" => array('int'),
		);


		public function size()
		{
			if (!$this->size) {
				$this->size = filesize($this->get_path());
			}

			return $this->size;
		}


		public function suffix()
		{
			if (!$this->suff) {
				if (strpos($this->name(), '.') > 0) {
					$this->suff = explode('.', $this->name());
					$this->suff = $this->suff[count($this->suffix)-1];
				} else {
					$this->suff = null;
				}
			}

			return $this->suffix;
		}


		public function get_path()
		{
			if ($this->path) {
				return $this->path.'/'.$this->name;
			} else {
				if ($this->hash()) {
					return ROOT.self::DIR.'/'.$this->hash().'.'.$this->suffix();
				}
			}

			return null;
		}


		public function hash()
		{
			if (!$this->hash && $this->path) {
				$this->hash = md5(fread($this->get_path(), round($this->size()*.25)));
			}

			return $this->hash;
		}


		public function name()
		{
			if ($this->name) {
				return $this->name;
			} else {
				return $this->hash.'.'.$this->suff;
			}
		}


		/** Remove file from filesystem
		 * @return $this
		 */
		public function remove()
		{
			unlink($this->get_path());
			return $this;
		}


		/** Clear tmp directory
		 * @return void
		 */
		public static function clear_tmp()
		{
			self::remove_directory(ROOT.self::TMP_DIR);
			mkdir(ROOT.self::TMP_DIR, 0777, true);
		}


		/** Fetch file from remote URL
		 * @param string $url URL of file
		 * @param string $dir Directory to save file
		 * @return self
		 * @throws System\Error\File
		 * @throws System\Error\Connection
		 */
		static function fetch($url, $dir = null)
		{
			$u = explode('/', $url);
			$name = end($u);
			$e = explode('.', $name);
			unset($e[0]);
			$suffix = implode('.', $e);
			$data = \System\Offcom\Request::get($url);

			if ($data->ok()) {
				if (!$dir) {
					$dir = ROOT.self::TMP_DIR;
				}

				\System\Directory::check($dir);
				$magic = strtoupper(gen_random_string(10));
				$tmp_name = $dir.'/'.$magic.self::FETCHED_SIGN.'.'.$suffix;

				if (!file_put_contents($tmp_name, $data->content, LOCK_EX)) {
					throw new \System\Error\File(sprintf('Could not temporarily save fetched file into "%s".', $tmp_name));
				}

				return new self(array("filename" => $name, "dirpath" => dirname($dir), "suffix" => $suffix, "tmp_name" => $tmp_name));
			} else throw new \System\Error\Connection('Couldn\'t fetch file', sprintf('HTTP error %s ', $data->status));
		}


		/** Move file to another location
		 * @param string $where   Destination
		 * @param bool   $use_tmp
		 * @return $this;
		 */
		public function move($where, $use_tmp = false)
		{
			if (!is_null($op = $this->get_path())) {
				$np = (dirname($where) == $where) ? dirname($where).'/'.$this->filename:$where;
				if (file_exists($np)) {
					unlink($np);
				}

				if (!rename($op, $np)) {
					$this->errors[] = 'move-failed';
				}

				return $this;
			} else throw new \System\Error\File('Cannot move empty file object.');
		}


		/** Remove postfix from file name
		 * @param string $name Name of file
		 * @param bool   $all  Return all postfixes
		 * @return string New name
		 */
		public static function remove_postfix($name, $all = false)
		{
			$temp = explode('.', $name);
			if (count($temp) > 1) {
				array_pop($temp);
				return $all ? reset($temp):implode('.', $temp);
			}
			return $name;
		}


		/** Save file content
		 * @param string $filepath
		 * @param string $content
		 * @param int    $mode
		 * @return bool
		 */
		public static function save_content($filepath, $content, $mode = self::MOD_DEFAULT)
		{
			return self::put($filepath, $content, $mode);
		}


		/** Put string data into file
		 * @param string $path
		 * @param string $content
		 * @param int    $mode
		 * @throws System\Error\Permissions
		 * @return bool
		 */
		public static function put($path, $content, $mode = null)
		{
			if (\System\Directory::check($d = dirname($path)) && (($ex = file_exists($path)) || is_writable($d))) {
				if (!$ex || is_writable($path)) {
					$write = file_put_contents($path, $content);
					$mod = true;

					if (!$ex && is_null($mode)) {
						chmod($path, self::MOD_DEFAULT);
					}

					if (!is_null($mode)) {
						if (!$mod = @chmod($path, $mode)) {
							throw new \System\Error\Permissions(sprintf('Failed to set %s permissions on file "%s".', $mode, $path));
						}
					}

					return $write && $mod;
				} else throw new \System\Error\Permissions(sprintf('Failed to write data into file "%s". Check your permissions.', $path));
			} else throw new \System\Error\Permissions(sprintf('Failed to write data into file "%s". Parent directory is not writeable.', $path));

			return $action;
		}


		/** Read file data
		 * @param string $path
		 * @param bool   $silent
		 * @throws System\Error\File
		 * @throws System\Error\Permissions
		 * @return string
		 */
		public static function read($path, $silent = false)
		{
			if (\System\Directory::check(dirname($path), false) && file_exists($path)) {
				if (is_readable($path)) {
					return file_get_contents($path);
				} else if (!$silent) throw new \System\Error\Permissions(sprintf('Failed to read file "%s". It is not readable.', $path));
			} else if (!$silent) throw new \System\Error\File(sprintf('Failed to read file "%s". It does not exist.', $path));

			return false;
		}
	}
}
