<?

$path = Compiler::get('path');
$minified = Compiler::swap('minified');

Compiler::$banned_files = array(
	'.htaccess',
	'install.php',
	'meta/checksum',
	'meta/changelog',
	'meta/version',
);

foreach ($minified as $file) {
	Compiler::$banned_files[] = str_replace($path['dir-data'].'/', '', $file);
}

$result = true;

