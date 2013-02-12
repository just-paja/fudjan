<?

ob_implicit_flush(false);

/** Find system class package and include it or lookup develop files. You can
 * unpack it to be accessible by autoloader.
 */
if ($sys_package = stream_resolve_include_path(ROOT."/lib/include/system.php")) {
	require_once $sys_package;
}

if (!defined('YAWF_PACKED')) {
	require_once ROOT."/lib/class/system/loader.php";
	require_once ROOT."/lib/include/constants.php";
	require_once ROOT."/lib/include/functions.php";
	require_once ROOT."/lib/include/core.php";
	require_once ROOT."/lib/include/aliases.php";
}

