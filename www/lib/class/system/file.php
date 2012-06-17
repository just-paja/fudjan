<?

namespace System
{
	class File extends Model\Attr
	{
		const DIR = '/var/files';
		const TMP_DIR = '/var/tmp';
		const FETCHED_SIGN = '-FETCHED';

		// setup operations directory
		private static $operations = self::TMP_DIR;

		static protected $attrs = array(
			"string" => array('filename', 'dirpath', 'suffix', 'mime-type', 'tmp_name'),
			"int" => array('size'),
		);
		static protected $required = array();
		static protected $instances = array();

		private $content;

		static function clear_tmp()
		{
			self::remove_directory(ROOT.self::TMP_DIR);
			mkdir(ROOT.self::TMP_DIR, 0777, true);
			message("info", _('Informace'), _('Dočasné soubory byly pročištěny.'), true);
		}


		static function remove_directory($dir)
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


		static function access_dir($dir)
		{
			if(strpos($dir, ROOT.'/var') !== false){
				$path = array_filter(explode('/', $dir));
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
			$content = Request::get($url);

			if (!$dir) {
				$dir = ROOT.self::TMP_DIR;
			}

			$magic = strtoupper(gen_random_string(10));
			$tmp_name = self::access_dir($dir).'/'.$magic.self::FETCHED_SIGN.'.'.$suffix;
			!!(file_put_contents($tmp_name, $content, LOCK_EX)) ?
				message("success", _('Nahrávání souboru'), sprintf(_('Soubor \'%s\' byl úspěšně uložen'), $name), true):
				message("error", _('Nahrávání souboru'), sprintf(_('Soubor \'%s\' se nepovedlo uložit'), $name));

			return new self(array("filename" => $name, "dirpath" => dirname($dir), "suffix" => $suffix, "tmp_name" => $tmp_name));
		}


		function get_tmp_url()
		{
			return $this->__get('tmp_name');
		}
		
		
		function move($where, $use_tmp = false)
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
		
		
		function save($where)
		{
			return $this->move($where, true);
		}
		
		
		static function remove_postfix($name, $all = false)
		{
			$temp = explode('.', $name);
			if (count($temp) > 1) {
				array_pop($temp);
				return $all ? reset($temp):implode('.', $temp);
			}
			return $name;
		}
	}
}
