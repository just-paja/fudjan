<?

namespace System
{
	/** Handles archives.
	 */
	class Archive
	{
		/*
		 * Archive handling class
		 * DONE: gz, bz2, tar
		 * TODO: zip, permissions
		*/

		const HEADER_BZ2 = "compress.bzip2://";
		const HEADER_GZ = "compress.gzip://";

		static $types = array( "raw", "tar", "tar.bz2", "tar.gz", "bz2", "gz" );
		static $msg_title = "Práce s archivem dat";

		private $dirs = array();
		private $files = array();

		private $extract_path;
		private $path;
		private $data;
		private $type;
		private $fp;
		private $opts = array();


		function __construct($path)
		{
			$this->type = 'raw';
			$this->path = $path;
		}


		public function get_path()
		{
			return $this->extract_path;
		}


		public static function from($type, $path, $tar = false)
		{
			$method = 'read_'.$type;
			$tmp = new self($path);
			$tmp->$method($tar);
			return $tmp;
		}


		public function get_extract_path()
		{
			return $this->extract_path;
		}


		public function set_path($path)
		{
			if ($this->is_temp()) {
				unlink($this->path);
			}
			$this->path = $path;
		}


		public function is_temp($really = NULL)
		{
			if ($really === NULL) {
				return isset($this->opts['temp']) && $this->opts['temp'];
			} else {
				$this->opts['temp'] = !!$really;
				return $this;
			}
		}


		public function read_bz2($tar = false)
		{
			if($tar) $this->type = 'tar';
			$this->data[$this->type] = file_get_contents(self::HEADER_BZ2.$this->path);

			if ($this->data[$this->type] === false) {
				throw new \System\Error\Format(sprintf('Cannot read bz2 archive "%s"', $this->path));
			}

			return $this;
		}


		public function read_gz($tar = false)
		{
			if($tar) $this->type = 'tar';
			$this->data[$this->type] = \System\File::read(self::HEADER_GZ.$this->path);
			return $this;
		}


		public function extract($path_prefix = NULL)
		{
			if (!$path_prefix) {
				$path_prefix = $this->path.'_files';
			}

			if ($this->type != 'raw')
			{
				if (in_array($this->type, self::$types)) {
					$d = $this->decompress(true);
				} else throw new \System\Error\Format(sprintf('Unknown archive format "%s", could not save archive.', $this->type));

				if ($d) {
					$this->extract_path = $path_prefix;

					foreach ($this->dirs as $dir) {
						\System\Directory::check($path_prefix.'/'.$dir);
					}

					foreach ($this->files as $file) {
						if (file_exists($path_prefix.'/'.$file['name'])) {
							$a = unlink($path_prefix.'/'.$file['name']);
						}
						$fp = fopen($path_prefix.'/'.$file['name'], "wb");
						fwrite($fp, $file['content']);
						fclose($fp);
					}
				}
			}
			return $this;
		}


		public function decompress()
		{
			if (in_array($this->type, self::$types)) {
				if ($this->type == 'tar') {
					$this->set_path(ROOT.File::TMP_DIR.'/'.strtoupper(gen_random_string(10)).'.tar');
					$this->is_temp(true);
					\System\File::put($this->path, $this->data['tar']);

					$files = array();
					$dirs = array();

					while ($block = $this->tar_read_block()) {
						if (!$this->read_tar_header($block, $header)) {
							throw new \System\Error\Format('Tar headers seem to be brokend. Could not unpack tarball.');
							break;
						}
						if ($header['typeflag'] == 'L' && !$this->read_tar_long_header($header)) {
							throw new \System\Error\Format('Tar headers seem to be brokend. Could not unpack tarball.');
							break;
						}

						if (!$header['filename']) {
							continue;
						}

						if ($header['typeflag'] == 5) {
							$dirs[] = $header['filename'];
						} elseif ($header['typeflag'] == 'g') {
							$tarinfo = array_merge($header, (array) $this->tar_read_block());
						} elseif ($header['typeflag'] == 2) {
						} else {
							$file = array("name" => $header['filename'], "content" => '');
							$n = floor($header['size']/512);

							for($i=0; $i<$n; $i++) $file['content'] .= $this->tar_read_block();
							if(($header['size'] % 512) != 0) $file['content'] .= substr($this->tar_read_block(), 0, $header['size']%512);
							$files[] = $file;
						}
					}
					$this->dirs = $dirs;
					$this->files = $files;
					return true;
				} else {
					throw new \System\Error\Format(sprintf('Could not unpack archive, format "%s" is not supported.', $this->type));
					return false;
				}
			} else {
				throw new \System\Error\Format(sprintf('Could not save archive, format "%s" is not supported.', $this->type));
				return false;
			}
		}


		private function open_file_pointer()
		{
			switch($this->type){
				case 'gz': $this->fp = gzopen($this->path, 'r+'); break;
				case 'bz2': $this->fp = bzopen($this->path, 'r+'); break;
				default: $this->fp = fopen($this->path, 'r+'); break;
			}
		}


		private function tar_read_block()
		{
			if(!is_resource($this->fp)) $this->open_file_pointer();
			if(is_resource($this->fp)){
				if($this->type == 'gz') $block = @gzread($this->fp, 512);
				elseif($this->type == 'bz2') $block = @bzread($this->fp, 512);
				else $block = @fread($this->fp, 512);
			}
			return $block;
		}


		private function read_tar_header($bin_data, &$header)
		{
			static $x;
			if (!strlen($bin_data) || strlen($bin_data) != 512) {
				$header['filename'] = '';
				return false;
			}
			if (!is_array($header)) $header = array();

			$v_checksum = 0;                                                            // Calculate the checksum
			for($i=0; $i<148; $i++) $v_checksum+=ord(substr($bin_data,$i,1));           // First part of the header
			for($i=148; $i<156; $i++) $v_checksum += ord(' ');                          // Ignore the checksum value and replace it by ' ' (space)
			for($i=156; $i<512; $i++) $v_checksum += ord(substr($bin_data,$i,1));       // Last part of the header

			$v_data = unpack(
				 "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/"
				."a8checksum/a1typeflag/a100link/a6magic/a2version/"
				."a32uname/a32gname/a8devmajor/a8devminor",
				 $bin_data
			);

			$header['checksum'] = OctDec(trim($v_data['checksum']));

			if ($header['checksum'] != $v_checksum && strpos($header['filename'], "/\=/")) {
				$header['filename'] = '';                                                 // Look for last block (empty block)
				if(($v_checksum == 256) && ($header['checksum'] == 0)) return true;
				return false;
			}

			$header['filename'] = $v_data['filename'];                                  // Extract the properties
			$header['mode'] = OctDec(trim($v_data['mode']));
			$header['uid'] = OctDec(trim($v_data['uid']));
			$header['gid'] = OctDec(trim($v_data['gid']));
			$header['size'] = OctDec(trim($v_data['size']));
			$header['mtime'] = OctDec(trim($v_data['mtime']));
			if(($header['typeflag'] = $v_data['typeflag']) == "5") $header['size'] = 0;
			$header['link'] = trim($v_data['link']);
			/* ----- All these fields are removed form the header because they do not carry interesting info
			$header[magic] = trim($v_data[magic]);
			$header[version] = trim($v_data[version]);
			$header[uname] = trim($v_data[uname]);
			$header[gname] = trim($v_data[gname]);
			$header[devmajor] = trim($v_data[devmajor]);
			$header[devminor] = trim($v_data[devminor]);
			*/

			return true;
		}


		private function read_tar_long_header(&$header)
		{
			$v_filename = '';
			$n = floor($v_header['size']/512);
			for ($i=0; $i<$n; $i++) {
				$v_content = $this->_readBlock();
				$v_filename .= $v_content;
			}
			if (($v_header['size'] % 512) != 0) {
				$v_content = $this->_readBlock();
				$v_filename .= trim($v_content);
			}

			$v_binary_data = $this->tar_read_block();                                   // Read the next header
			if ($this->read_tar_header($v_binary_data, $v_header)) {
				return false;
			}

			$v_filename = trim($v_filename);
			$v_header['filename'] = $v_filename;
			return true;
		}
	}
}
