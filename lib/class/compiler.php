<?

abstract class Compiler
{
	private static $name;
	private static $info;
	private static $package;
	private static $version;
	private static $path;
	private static $swap;
	private static $meta;
	private static $messages;

	public static $files;
	public static $banned_files;

	public static function prepare_workspace()
	{
		self::$files = array();
		self::$swap = array();
		self::$banned_files = array();
		self::$messages = array();
		self::$package = array();

		if (!is_null(self::$meta)) {
			self::$version = self::$meta['package-version'];
			self::$name = self::$meta['package-name'];

			self::$package = array(
				"dir-output" => ROOT.'/packages',
				"dir-src"    => ROOT.'/www',
				"dir-make"   => self::$meta['dir-make'],
			);

			self::$path = array(
				"output"        => self::$package['dir-output'].'/'.self::$name.'-'.self::$version.'.tar.bz2',
				"output-dir"    => self::$package['dir-output'],
				"output-temp"   => self::$package['dir-output'].'/'.self::$name.'-'.self::$version.'.tar',
				"dir-temp"      => self::$package['dir-output'].'/'.self::$name.'-temp',
			);

			self::$path = array_merge(self::$path, array(
				"dir-data"      => self::$path['dir-temp'].'/temp/data',
				"dir-meta"      => self::$path['dir-temp'].'/temp/meta',
				"file-tar"      => self::$path['dir-temp'].'/temp/tmp.tar',
				"file-version"  => self::$path['dir-temp'].'/temp/meta/version',
				"file-checksum" => self::$path['dir-temp'].'/temp/meta/checksum',
			));

			// Prepare workspace directories
			@unlink(self::$path['output']);
			@exec('rm -Rf '.self::$path['dir-temp']);
			@mkdir(self::$path['dir-temp'], 0770, true);
			@mkdir(self::$path['dir-data'], 0770, true);
			@mkdir(self::$path['dir-meta'], 0770, true);
		} else throw new Exception('You must provide compiler with meta information.');
	}


	/** Setup meta info for compiler
	 * @param array $meta
	 * @returns void
	 */
	public static function provide_meta(array $meta)
	{
		self::$meta = $meta;
	}


	/** Public getter of private properties
	 * @param string $what Property name
	 */
	public static function get($what)
	{
		if (isset(self::$$what)) {
			return self::$$what;
		}
	}


	/** Run pwf command
	 * @param string $what Path to command relative to make dir
	 */
	public static function run($what)
	{
		if (file_exists($p = self::$package['dir-make'].'/'.$what.'.php'))
		{
			require_once $p;
			return isset($result) ? $result:false;
		}

		return true;
	}


	/** Save or get temporary data. It can be accessible from global scope
	 * @param string $what Key to data
	 * @param mixed  $val
	 */
	public static function swap($what, $val = null)
	{
		if (!is_null($val)) {
			self::$swap[$what] = $val;
		}

		if (isset(self::$swap[$what])) {
			return self::$swap[$what];
		}
	}


	public static function process($name, $msg, array $data, \Closure $lambda)
	{
		$result = self::run($name.'.pre');

		$process = new Compiler\Process($msg, $data, $lambda);
		$result = $result && $process->run();
		$result = $result && self::run($name);

		if (!$result) {
			throw new Exception(sprintf('Suprocess of "%s" has failed.', $name));
		}

		return $result;
	}


	/** Compress PHP code inside file
	 * @param string $src URL or code
	 * @returns string
	 */
	public static function compress_php_src($src) {
		// Whitespaces left and right from this signs can be ignored
		static $IW = array(
			T_CONCAT_EQUAL,			// .=
			T_DOUBLE_ARROW,			// =>
			T_BOOLEAN_AND,			 // &&
			T_BOOLEAN_OR,			  // ||
			T_IS_EQUAL,				// ==
			T_IS_NOT_EQUAL,			// != or <>
			T_IS_SMALLER_OR_EQUAL,	 // <=
			T_IS_GREATER_OR_EQUAL,	 // >=
			T_INC,					 // ++
			T_DEC,					 // --
			T_PLUS_EQUAL,			  // +=
			T_MINUS_EQUAL,			 // -=
			T_MUL_EQUAL,				// *=
			T_DIV_EQUAL,				// /=
			T_IS_IDENTICAL,			// ===
			T_IS_NOT_IDENTICAL,		// !==
			T_DOUBLE_COLON,			// ::
			T_PAAMAYIM_NEKUDOTAYIM,	// ::
			T_OBJECT_OPERATOR,		 // ->
			T_DOLLAR_OPEN_CURLY_BRACES, // ${
			T_AND_EQUAL,				// &=
			T_MOD_EQUAL,				// %=
			T_XOR_EQUAL,				// ^=
			T_OR_EQUAL,				// |=
			T_SL,					  // <<
			T_SR,					  // >>
			T_SL_EQUAL,				// <<=
			T_SR_EQUAL,				// >>=
		);

		if(is_file($src)) {
			if(!$src = file_get_contents($src)) {
				return false;
			}
		}

		$tokens = token_get_all($src);
		$new = "";
		$c = sizeof($tokens);
		$iw = false; // ignore whitespace
		$ih = false; // in HEREDOC
		$ls = "";	// last sign
		$ot = null;  // open tag

		for($i = 0; $i < $c; $i++) {
			$token = $tokens[$i];
			if(is_array($token)) {
				list($tn, $ts) = $token; // tokens: number, string, line
				$tname = token_name($tn);
				if($tn == T_INLINE_HTML) {
					$new .= $ts;
					$iw = false;
				} else {
					if($tn == T_OPEN_TAG) {
						if(strpos($ts, " ") || strpos($ts, "\n") || strpos($ts, "\t") || strpos($ts, "\r")) {
							$ts = rtrim($ts);
						}
						$ts .= " ";
						$new .= $ts;
						$ot = T_OPEN_TAG;
						$iw = true;
					} elseif ($tn == T_OPEN_TAG_WITH_ECHO) {
						$new .= $ts;
						$ot = T_OPEN_TAG_WITH_ECHO;
						$iw = true;
					} elseif($tn == T_CLOSE_TAG) {
						if($ot == T_OPEN_TAG_WITH_ECHO) {
							$new = rtrim($new, "; ");
						} else {
							$ts = " ".$ts;
						}
						$new .= $ts;
						$ot = null;
						$iw = false;
					} elseif(in_array($tn, $IW)) {
						$new .= $ts;
						$iw = true;
					} elseif($tn == T_CONSTANT_ENCAPSED_STRING
							|| $tn == T_ENCAPSED_AND_WHITESPACE)
					{
						if($ts[0] == '"') {
							$ts = addcslashes($ts, "\n\t\r");
						}
						$new .= $ts;
						$iw = true;
					} elseif($tn == T_WHITESPACE) {
						$nt = @$tokens[$i+1];
						if(!$iw && (!is_string($nt) || $nt == '$') && !in_array($nt[0], $IW)) {
							$new .= " ";
						}
						$iw = false;
					} elseif($tn == T_START_HEREDOC) {
						$new .= "<<<S\n";
						$iw = false;
						$ih = true; // in HEREDOC
					} elseif($tn == T_END_HEREDOC) {
						$new .= "S;";
						$iw = true;
						$ih = false; // in HEREDOC
						for($j = $i+1; $j < $c; $j++) {
							if(is_string($tokens[$j]) && $tokens[$j] == ";") {
								$i = $j;
								break;
							} else if($tokens[$j][0] == T_CLOSE_TAG) {
								break;
							}
						}
					} elseif($tn == T_COMMENT || $tn == T_DOC_COMMENT) {
						$iw = true;
					} else {
						$new .= $ts;
						$iw = false;
					}
				}
				$ls = "";
			} else {
				if(($token != ";" && $token != ":") || $ls != $token) {
					$new .= $token;
					$ls = $token;
				}
				$iw = true;
			}
		}
		return $new;
	}


	/** Report a message
	 * @param string $msg
	 */
	public static function message($msg)
	{
		self::$messages[] = $msg;
	}


	/** Read directory contents
	 * @param string $dir
	 * @param &array $files
	 * @param &array $directories
	 * @param &array $used
	 */
	public static function read_dir($dir, array &$files, array &$directories, array &$used = array())
	{
		$od = opendir($dir);
		while ($f = readdir($od)) {
			if ($f != '.' && $f != '..') {
				$fp = $dir.'/'.$f;
				if (is_dir($fp)) {
					self::read_dir($fp, $files, $directories, $used);
					if (!in_array($fp, $used)) {
						$directories[] = $fp;
					}
				} else {
					if (!in_array($fp, $used)) {
						$files[] = $fp;
						$used[] = $fp;
					}
				}
			}
		}
		closedir($od);
	}

}
