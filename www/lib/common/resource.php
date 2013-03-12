<?

/** Get resource of type defined in system. Usually styles or scripts
 * @package    system
 * @subpackage resources
 */
define('ROOT', realpath(__DIR__.'/../../'));
require_once ROOT."/etc/init.d/core.php";

System\Init::basic();
System\Resource::request();
