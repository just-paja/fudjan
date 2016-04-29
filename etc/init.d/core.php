<?php

/** Load pwf essentials.
 * @package init
 */

ob_implicit_flush(false);

if (!defined('BASE_DIR')) {
	define('BASE_DIR', ROOT);
}

function preloadFudjanCore($useCache = false) {
  if ($useCache && file_exists(BASE_DIR.'/var/cache/runtime/core.php')) {

    require_once BASE_DIR.'/var/cache/runtime/core.php';

  } else {

    require_once ROOT."/lib/class/system/loader.php";
    require_once ROOT."/lib/class/system/directory.php";
    require_once ROOT."/lib/class/system/composer.php";
    require_once ROOT."/lib/class/system/init.php";
    require_once ROOT."/lib/class/system/template/tag.php";

  }

  require_once ROOT."/lib/include/constants.php";
  require_once ROOT."/lib/include/functions.php";
  require_once ROOT."/lib/include/aliases.php";

  System\Init::basic();
}
