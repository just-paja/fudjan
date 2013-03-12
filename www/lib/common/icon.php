<?

/** Script to handle icon paths according to system settings. Outputs raw image
 * data of the icon or default
 * @package    system
 * @subpackage resources
 */

define("ROOT", realpath(__DIR__.'/../../'));
require_once ROOT."/etc/init.d/core.php";

System\Init::basic();

$exp = new DateTime();
$exp->setTimezone(new DateTimeZone("Europe/Prague"));

$path = '';
$theme = System\Input::get('theme');
$theme = $theme ? $theme:System\Template::get_icon_theme();
$size = System\Input::get('size');
$catg = System\Input::get('catg');
$name = System\Input::get('name');

file_exists($path = ROOT.System\Template::DIR_ICONS.'/'.$theme.'/'.$size.'/'.$catg.'/'.$name.'.png') ||
file_exists($path = ROOT.System\Template::DIR_ICONS.'/'.$theme.'/'.$size.'/'.$catg.'/default.png') ||
file_exists($path = ROOT.System\Template::DIR_ICONS.'/'.$theme.'/'.$size.'/default.png') ||
file_exists($path = ROOT.System\Template::DIR_ICONS.'/'.System\Template::DEFAULT_ICON_THEME.'/'.$size.'/'.$catg.'/default.png') ||
file_exists($path = ROOT.System\Template::DIR_ICONS.'/'.System\Template::DEFAULT_ICON_THEME.'/'.$size.'/default.png') ||
($path = '');

if ($path) {

	$cont = System\File::read($path);
	header("HTTP/1.1 200 OK");
	header("Content-Type: image/png");
	header("Content-Length: ".strlen($cont));
	header("Pragma: public,max-age=604800");
	header("Cache-Control: public,max-age=604800");
	header("Expires: ".gmdate('D, d M Y G:i:s T', time()+7*86400));
	header("Age: 0");

	echo $cont;
	exit;

} throw new \System\Error\NotFound();


