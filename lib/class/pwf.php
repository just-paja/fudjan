<?

/** Helper container for pwf packaging
 */
abstract class Pwf
{
	const REF_DEFAULT = 'master';


	public static function read_meta_info($path)
	{
		$info = json_decode(file_get_contents($path.'/lib/make.d/info.json'), true);
		$info['dir-make'] = $path.'/lib/make.d';
		$info['package-version'] = self::get_version($path, self::get_branch($path));
		return $info;
	}


	public static function get_version($directory)
	{
		return exec("git describe --tags");
	}


	public static function get_branch($path)
	{
		return exec("cd ".$path."; git status | grep branch | head -1 | cut -d \" \" -f 4");
	}
}
