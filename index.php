<?

/** Basic pwf page, that is called most frequently. Runs page init
 * @package init
 */
define("ROOT",  __DIR__);

if (!defined('BASE_DIR')) {
	define('BASE_DIR', ROOT);
}

require_once ROOT."/etc/init.d/core.php";
require_once ROOT."/etc/init.d/page.php";

