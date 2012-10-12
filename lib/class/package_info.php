<?

class PackageInfo
{
	private static $name;
	private static $info;
	private static $package;
	private static $version;
	private static $path;
	private static $swap;

	public static $files;
	public static $banned_files;

	public static function load_project_meta($project)
	{
		self::$files = array();
		self::$swap = array();
		self::$banned_files = array();

		self::$info = json_decode(file_get_contents(ROOT.'/'.$project.'/make/info.json'), true);
		self::$name = self::$info['package-name'];
		self::$package = array(
			"dir-output" => ROOT.'/packages',
			"dir-src"    => ROOT.'/'.$project.'/www',
			"dir-make"   => ROOT.'/'.$project.'/make',
		);

		self::$version = self::$info['package-version'] = self::get_version();
		self::$path = array(
			"output"        => self::$package['dir-output'].'/'.self::$name.'-'.self::$version.'.tar.bz2',
			"output-temp"   => self::$package['dir-output'].'/'.self::$name.'-'.self::$version.'.tar',
			"dir-temp"      => self::$package['dir-output'].'/temp',
			"dir-data"      => self::$package['dir-output'].'/temp/data',
			"dir-meta"      => self::$package['dir-output'].'/temp/meta',
			"file-temp"     => self::$package['dir-output'].'/temp/tmp.tar',
			"file-version"  => self::$package['dir-output'].'/temp/meta/version',
			"file-checksum" => self::$package['dir-output'].'/temp/meta/checksum',
		);
	}


}
