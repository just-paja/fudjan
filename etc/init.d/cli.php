<?php

if (!defined('BASE_DIR')) {
	define('BASE_DIR', ROOT);
}

require_once ROOT."/etc/init.d/core.php";

\Helper\Cli::parse_command($argv);

