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
		$ver = exec("cd ".$directory."; git log --pretty=\'%b\' ".self::get_branch($directory)." | sort | uniq | wc -l");
		$subver = floor($ver/100);
		$microver = floor($ver - $subver * 100);
		$mver = 0;

		return $mver.".".$subver.".".$microver;
	}


	public static function get_branch($path)
	{
		return exec("cd ".$path."; git status | grep branch | head -1 | cut -d \" \" -f 4");
	}
}
