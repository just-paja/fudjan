<?

namespace System
{
	class File extends Model\Attr
	{
		const DIR = '/var/files';
		const TMP_DIR = '/var/tmp';
		const FETCHED_SIGN = '-FETCHED';
		const MOD_DEFAULT = 0664;

		// setup operations directory
		private static $operations = self::TMP_DIR;

		static protected $attrs = array(
			"filename"  => array('varchar'),
			"dirpath"   => array('varchar'),
			"suffix"    => array('varchar'),
			"mime_type" => array('varchar'),
			"tmp_name"  => array('varchar'),
			"size"      => array('int'),
		);
		static protected $required = array();
		static protected $instances = array();

		private $content;


		function __destruct()
		{
			//~ $this->remove();
		}


		public static function clear_tmp()
		{
			self::remove_directory(ROOT.self::TMP_DIR);
			mkdir(ROOT.self::TMP_DIR, 0777, true);
			message("info", _('Informace'), _('Dočasné soubory byly pročištěny.'), true);
		}


		public static function remove_directory($dir)
		{
			if(strpos('..', $dir) === false){
				if(strpos($dir, ROOT.self::$operations) !== 0) $dir = ROOT.self::$operations.$dir;
				if(is_dir($dir)){
					$dp = opendir($dir);
					while($f = readdir($dp)){
						if($f != '..' && $f != '.'){
							!!(is_dir($dir.'/'.$f)) ?
								self::remove_directory($dir.'/'.$f):
								unlink($dir.'/'.$f);
						}
					}
					rmdir($dir);
				}
			}
		}


		public static function access_dir($dir)
		{
			if(strpos($dir, ROOT.'/var') !== false){
				$path = array_filter(explode('/', $dir));
				$dp = '';
				foreach($path as $p){
					$dp .= '/'.$p;
					if(!is_dir($dp)) mkdir($dp);
				}
			}
			return $dir;
		}


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

				$magic = strtoupper(gen_random_string(10));
				$tmp_name = self::access_dir($dir).'/'.$magic.self::FETCHED_SIGN.'.'.$suffix;
				!!(file_put_contents($tmp_name, $data->content, LOCK_EX)) ?
					message("success", _('Nahrávání souboru'), sprintf(_('Soubor \'%s\' byl úspěšně uložen'), $name), true):
					message("error", _('Nahrávání souboru'), sprintf(_('Soubor \'%s\' se nepovedlo uložit'), $name));

				return new self(array("filename" => $name, "dirpath" => dirname($dir), "suffix" => $suffix, "tmp_name" => $tmp_name));
			} else throw new \System\Error\Connection('Couldn\'t fetch file', sprintf('HTTP error %s ', $data->status));
		}


		public function get_tmp_url()
		{
			return $this->__get('tmp_name');
		}


		public function move($where, $use_tmp = false)
		{
			$op = $use_tmp ? $this->__get('tmp_name'):$this->__get('dirpath').'/'.$this->__get('filename');
			$np = (dirname($where) == $where) ? dirname($where).'/'.$this->filename:$where;
			if (file_exists($np)) {
				unlink($np);
			}
			if (!rename($op, $np)) {
				$this->errors[] = 'move-failed';
			}
			return $this;
		}


		public function remove()
		{
			unlink($this->tmp_name);
			return $this;
		}


		public function save($where)
		{
			return $this->move($where, true);
		}


		public static function remove_postfix($name, $all = false)
		{
			$temp = explode('.', $name);
			if (count($temp) > 1) {
				array_pop($temp);
				return $all ? reset($temp):implode('.', $temp);
			}
			return $name;
		}


		public static function save_content($filepath, $content, $mode = self::MOD_DEFAULT)
		{
			return self::put($filepath, $content, $mode);
		}


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


		public static function read($path, $silent = false)
		{
			if (\System\Directory::check(dirname($path)) && file_exists($path)) {
				if (is_readable($path)) {
					return file_get_contents($path);
				} else if (!$silent) throw new \System\Error\Permissions(sprintf('Failed to read file "%s". It is not readable.', $path));
			} else if (!$silent) throw new \System\Error\File(sprintf('Failed to read file "%s". It does not exist.', $path));

			return false;
		}
	}
}
