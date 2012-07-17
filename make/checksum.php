<?

$path = PackageInfo::get('path');
$minified = PackageInfo::swap('minified');

PackageInfo::$banned_files = array(
	'.htaccess',
	'install.php',
	'meta/checksum',
	'meta/changelog',
	'meta/version',
);

foreach ($minified as $file) {
	PackageInfo::$banned_files[] = str_replace($path['dir-data'].'/', '', $file);
}

