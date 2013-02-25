<?

define('ROOT', realpath(__DIR__.'/../../'));

require_once ROOT."/etc/init.d/core.php";

System\Init::full();

$db_status = array();
$cfg_status = array();
$db = cfg('database', 'list');

foreach ($db as $db_key=>$db_cfg) {
	$connected = System\Database::is_connected($db_key);
	$db_status["Database ".$db_key] = array(
		"message" => $connected ? l('Connected'):l('Not connected'),
		"class"   => $connected ? 'status-ok':'status-bad',
	);
}

$page_tree = cfg('pages');
unset($page_tree['cron'], $page_tree['api'], $page_tree['ajax-api']);
$empty = empty($page_tree);

$cfg_status['Page tree'] = array(
	"message" => $empty ? l('Seems to be empty'):l('Seems to be ok'),
	"class" => $empty ? 'status-ok':'status-bad',
);

$htaccess_current = sha1(System\Router::generate_rewrite_rules());
$htaccess_system = sha1(System\File::read(ROOT.'/.htaccess'));
$ok = $htaccess_current == $htaccess_system;

$cfg_status['Apache mod rewrite'] = array(
	"message" => $ok ? l('Seems to be ok'):l('Does not fit current version'),
	"class"   => $ok ? 'status-ok':'status-bad',
);

$status = array(
	"Database" => $db_status,
	"Settings" => $cfg_status,
);

System\Template::insert('system/status-full', array("status" => $status));

System\Output::set_template('pwf/status');
System\Output::set_format('html');
System\Output::out();
