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
		const DIR_TMP = '/var/tmp';
		const FETCHED_SIGN = '-FETCHED';
		const MOD_DEFAULT = 0664;
		const MIN_HASH_CHUNK_SIZE = 65536;

		protected $content;

		/** File attrmodel attributes */
		static protected $attrs = array(
			"hash" => array('varchar'), // DB
			"path" => array('varchar', 'is_null' => true),
			"name" => array('varchar'), // DB
			"mime" => array('varchar'),
			"suff" => array('varchar'), // DB
			"size" => array('int', "is_null" => true),
			"time" => array('int', "is_null" => true),
			"cached" => array('bool'),
			"temp"   => array('bool'),
			"keep"   => array('bool'),
		);


		/** Create instance from JSON
		 * @param string $json
		 */
		public static function from_json($json)
		{
			return new self(\System\Json::decode($json));
		}


		/** Create instance from JSON
		 * @param string $json
		 */
		public static function from_path($path)
		{
			if (!file_exists($path)) {
				$path = \System\Composer::resolve($path);
			}

			$file = new self(array(
				"path" => dirname($path),
				"name" => basename($path),
				"keep" => true
			));

			return $file->read_meta();
		}


		public static function from_tmp($path, $real_name)
		{
			$suff = self::get_suffix_from_name($real_name);

			if ($suff) {
				$real_name = substr($real_name, 0, mb_strlen($real_name) - mb_strlen($suff));
			}

			$file = self::from_path($path)->rename(\System\Url::gen_seoname($real_name).($suff ? '.'.$suff:''));
			$file->keep = false;
			return $file;
		}


		/** Destruction callback
		 * @return void
		 */
		public function __destruct()
		{
			if ($this->temp) {
				if (!$this->keep && self::check($this->get_path_temp())) {
					self::remove($this->get_path_temp());
				}
			}
		}


		/** Attr setter overload
		 * @param string $attr
		 * @param mixed  $value
		 */
		public function __set($attr, $value)
		{
			parent::__set($attr, $value);

			if ($attr == 'name') {
				if (strpos($value, '.')) {
					$split = explode('.', $value, 2);

					parent::__set('name', $split[0]);
					parent::__set('suff', $split[1]);
				}
			} else if ($attr == 'suff') {
				return parent::__set('suff', strtolower($value));
			}
		}


		/** Get file size
		 * @return int
		 */
		public function size()
		{
			if (is_null($this->size)) {
				if ($this->is_cached()) {
					$this->size = null;
					return strlen($this->get_content());
				} else {
					if ($this->exists()) {
						$this->size = filesize($this->get_path());
					} else {
						$this->size = null;
						return 0;
					}
				}
			}

			return $this->size;
		}


		/** Return suffix, try to extract it
		 * @return string
		 */
		public function suffix()
		{
			if (!$this->suff) {
				$this->suff = self::get_suffix_from_name($this->name());
			}

			return $this->suff;
		}


		public static function get_suffix_from_name($name)
		{
			$suff = null;

			if (strpos($name, '.') > 0) {
				$suff = explode('.', $name);
				$suff = $suff[count($suff)-1];
			}

			return $suff;
		}


		public function get_url()
		{
			return str_replace(BASE_DIR, '', $this->get_path());
		}


		/** Get path leading to file
		 * @return null|string Returns null if file can't be reached on filesystem
		 */
		public function get_path()
		{
			if ($this->path) {
				return $this->path.'/'.$this->get_full_name();
			} else {
				try {
					$hash = $this->hash();
				} catch(\System\Error\File $e) {
					$hash = null;
				}

				if ($hash) {
					return $this->get_path_hashed();
				}
			}

			return null;
		}


		/** Get path with hash
		 * @return string
		 */
		public function get_path_hashed()
		{
			return $this->get_path_hashed_dir().'/'.$this->get_path_hashed_name();
		}


		public function get_path_hashed_relative()
		{
			return $this->get_path_hashed_dir_relative().'/'.$this->get_path_hashed_name();
		}


		public function get_path_hashed_name()
		{
			return $this->get_time().($this->suffix() ? '.'.$this->suffix():'');
		}


		public function get_path_hashed_dir()
		{
			return BASE_DIR.self::DIR.'/'.$this->get_path_hashed_dir_relative();
		}


		public function get_path_hashed_dir_relative()
		{
			return substr($this->hash(), 0, 4).'/'.substr($this->hash(), 4, 4).'/'.substr($this->hash(), 8, 4).'/'.substr($this->hash(), 12);
		}


		/** Get temp path
		 * @return string
		 */
		public function get_path_temp()
		{
			return BASE_DIR.self::DIR_TMP.'/'.$this->hash().($this->suffix() ? '.'.$this->suffix():'');
		}


		/** Get hash describing the file
		 * @return string
		 */
		public function hash()
		{
			if (!$this->hash) {
				if ($this->is_cached()) {
					$chunks = chunk_split($this->get_content(), $this->get_digest_chunk_size(), '');
					$this->hash = $this->hash_chunk($chunks[0]);
				} else {
					if ($this->path && $this->exists()) {
						$fp = fopen($this->get_path(), 'r');
						$data = fread($fp, $this->get_digest_chunk_size());
						$this->hash = $this->hash_chunk($data);
						fclose($fp);
					} else throw new \System\Error\File('Cannot create hash from file. It does not exist on filesystem and is not cached.', var_export($this->path, true));
				}
			}

			return $this->hash;
		}


		/** Hash chunk of file blob
		 * @param string blob
		 * @return string
		 */
		private function hash_chunk($blob)
		{
			return md5($blob).'-'.$this->size();
		}


		public function get_time()
		{
			if (is_null($this->time)) {
				$this->time = time();
			}

			return $this->time;
		}


		/** Get size of file chunk that will be hashed
		 * @return int
		 */
		private function get_digest_chunk_size()
		{
			return $this->size() < self::MIN_HASH_CHUNK_SIZE ?
				$this->size():
				max(self::MIN_HASH_CHUNK_SIZE, round($this->size()*.25));
		}


		/** Get file name
		 * @return string
		 */
		public function name()
		{
			if ($this->name) {
				return $this->name;
			} else {
				return $this->hash;
			}
		}


		/** Get file name with suffix
		 * @return string
		 */
		public function get_full_name()
		{
			return $this->name().($this->suffix() ? '.'.$this->suffix():'');
		}


		/** Remove file from filesystem
		 * @return $this
		 */
		public function drop()
		{
			if ($this->exists()) {
				self::remove($this->get_path());
				return $this;
			} else throw new \System\Error\File('Cannot remove file. It does not exist on the filesystem.');
		}


		/** Clear tmp directory
		 * @return void
		 */
		public static function clear_tmp()
		{
			\System\Directory::remove(BASE_DIR.self::TMP_DIR);
			mkdir(BASE_DIR.self::TMP_DIR, 0777, true);
		}


		/** Fetch file from remote URL
		 * @param string $url URL of file
		 * @param string $dir Directory to save file
		 * @return self
		 * @throws System\Error\File
		 * @throws System\Error\Connection
		 */
		static function fetch($url)
		{
			$u = explode('/', $url);
			$name = end($u);
			$data = \System\Offcom\Request::get($url);

			if ($data->ok()) {
				$f = new self(array("name" => $name));
				$f->set_content($data->content);

				$f->size = $data->size;
				$f->mime = $data->mime;

				return $f;
			} else throw new \System\Error\Connection('Couldn\'t fetch file', sprintf('HTTP error %s ', $data->status));
		}


		/** Move file to another location
		 * @param string $where   Destination
		 * @return $this
		 */
		public function move($where)
		{
			$this->copy($where)->drop();
			$this->name = basename($where);
			$this->path = dirname($where);

			return $this;
		}


		public function rename($name)
		{
			return $this->move(dirname($this->get_path()).'/'.$name);
		}


		/** Copy file to another location
		 * @param string $where Location to move file to
		 * @return $this
		 */
		public function copy($where)
		{
			if ($this->exists()) {
				\System\Directory::check(dirname($where));

				if (!copy($this->get_path(), $where)) {
					throw new \System\Error\File('File copy from failed!',
						sprintf("From: %s", $this->get_path()),
						sprintf("To: %s", $where));
				}

				return $this;
			} else throw new \System\Error\File('Cannot copy file. It does not exist on the filesystem.');
		}


		/** Catalogize the file
		 * @return $this
		 */
		public function save()
		{
			if ($this->is_cached()) {
				self::put($this->get_path_hashed(), $this->get_content());
			} else {
				if ($this->exists()) {
					if (!$this->is_saved()) {
						$this->keep ?
							$this->copy($this->get_path_hashed()):
							$this->move($this->get_path_hashed());
					}
				} else throw new \System\Error\File(sprintf('Cannot save file "%s". It does not exist on the filesystem and not in memory.', $this->get_path()));
			}

			$this->path = null;
			return $this;
		}


		/** Save file into temporary storage
		 * return $this
		 */
		public function temp()
		{
			if ($this->is_cached()) {
				self::put($this->get_path_temp(), $this->get_content());
			} else {
				if ($this->exists()) {
					$this->copy($this->get_path_temp());
				}
			}

			$this->update_attrs(array(
				"name" => basename($this->get_path_temp()),
				"path" => dirname($this->get_path_temp()),
				"temp" => true,
			));

			return $this;
		}


		/** Dear sir, does this file exists?
		 * @return bool
		 */
		public function exists()
		{
			return !is_null($this->get_path()) && file_exists($this->get_path());
		}


		/** Dear sir, does this file exists?
		 * @return bool
		 */
		public function get_all_versions()
		{
			$versions = array();
			$dir_path = $this->get_path_hashed_dir();

			if (\System\Directory::check($dir_path, false)) {
				$version_files = \System\Directory::find($dir_path);
				$model = get_class($this);

				foreach ($version_files as $fp) {
					$file = $model::from_path($fp);
					$file->time = $file->name;
					$versions[] = $file;
				}
			}

			return $versions;
		}


		/** Dear sir, is this file cached in memory?
		 * @return bool
		 */
		public function is_cached()
		{
			return $this->cached;
		}


		/** Dear sir, is this file catalogized?
		 * @return bool
		 */
		public function is_saved()
		{
			return $this->hash && file_exists($this->get_path_hashed());
		}


		/** Dear sir, is this file empty? If cached, return true if null content size. If not, reads the size on fs.
		 * @return bool
		 */
		public function is_empty()
		{
			if ($this->is_cached()) {
				return !is_null($this->content);
			} else {
				if ($this->exists()) {
					return $this->size() <= 0;
				}
			}

			return true;
		}


		/** Read file into memory
		 * @return $this
		 */
		public function load()
		{
			if ($this->exists()) {
				$this->content = self::read($this->get_path());
				$this->is_cached = true;
				return $this;
			} else throw new \System\Error\File('Cannot read file. It does not exists on the filesystem.');
		}


		/** Unload the file from memory
		 * @return $this
		 */
		public function unload()
		{
			$this->content = null;
			$this->is_cached = false;
			return $this;
		}


		/** Get file content, read if necessary
		 * @return blob
		 */
		public function get_content()
		{
			if (!$this->is_cached()) {
				$this->load();
			}

			return $this->content;
		}


		/** Set file's content
		 * @param string $data
		 */
		public function set_content($data)
		{
			$this->content = $data;
			$this->cached = true;
			return $this;
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


		/** Export image object to JSON
		 * @param bool $encode Return encoded string
		 * @return string
		 */
		public function to_json($encode = true)
		{
			if ($this->is_saved()) {
				$data = array(
					"hash" => $this->hash(),
					"suff" => $this->suff,
					"name" => $this->name,
					"time" => $this->get_time(),
				);
			} else {
				$data = $this->data;
			}

			return $encode ? json_encode($data):$data;
		}


		public function to_object()
		{
			if (!$this->mime) {
				$this->read_meta();
			}

			return array(
				"url"    => $this->get_url(),
				"path"   => $this->get_path_hashed_relative(),
				"name"   => $this->name,
				"mime"   => $this->mime,
				"size"   => $this->size,
				"method" => $this->method ? $this->method:'save',
			);
		}


		public function read_meta()
		{
			if ($this->exists()) {
				$this->size = filesize($this->get_path());
				$this->mime = self::get_mime($this->get_path());
			}

			return $this;
		}


		/** Remove file from filesystem
		 * @param string $path
		 * @return bool
		 */
		public static function remove($path)
		{
			if (is_writable($path)) {
				return unlink($path);
			} else throw new \System\Error\Permissions(sprintf('Failed to remove file "%s". It is not accessible.', $path));
		}


		/** Check if file exists
		 * @param string $path
		 * @return bool
		 */
		public static function check($path)
		{
			return file_exists($path);
		}


		public static function get_mime($file)
		{
			if (function_exists("finfo_file")) {
				$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
				$mime = finfo_file($finfo, $file);
				finfo_close($finfo);
				return $mime;
			} else if (function_exists("mime_content_type")) {
				return mime_content_type($file);
			} else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
				// http://stackoverflow.com/a/134930/1593459
				$file = escapeshellarg($file);
				$mime = shell_exec("file -bi " . $file);
				return $mime;
			} else {
				return false;
			}
		}
	}
}
