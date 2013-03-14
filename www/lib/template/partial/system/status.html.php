<?

if (!defined('H_STATUS_DUMP')) {
	define('H_STATUS_DUMP', true);

	function dump_table(array $values)
	{
		Tag::table();
			Tag::thead(array("content" => Stag::tr(array("content" => array(
				Stag::th(array("content" => l('dump_bar_key'))),
				Stag::th(array("content" => l('dump_bar_value'))),
			)))));
			Tag::tbody();
				foreach ($values as $key=>$value) {
					if (is_array($value)) {
						$value = var_export($value, true);
					}

					if (is_object($value)) {
						$value = 'Instance of '.get_class($value);
					}

					Tag::tr(array("content" => array(
						Stag::td(array("content" => $key)),
						Stag::td(array("content" => $value)),
					)));
				}
			Tag::close('tbody');
		Tag::close('table');
	}
}

?>
<div class="status" id="devbar">
	<div class="status-dump">
		<div class="status-dump-inner">
			<ul class="bar-menu plain">
				<li><a href="" class="close"><span class="label"><?=l('dump_bar_hide')?></span></a></li>
				<li><a class="panel-status-time" href="#status-time">
					<span class="label"><?=l('dump_bar_exec_time')?></span>
					<span class="text"><?=t('dump_bar_exec_time_val', round(System\Flow::get_exec_time(), 6))?></span>
				</a></li>
				<li><a class="panel-status-packages" href="#status-packages">
					<span class="label"><?=l('dump_bar_packages')?></span>
					<span class="text"><?=System\Output::introduce()?></span>
				</a></li>
				<li><a class="panel-status-sql" href="#status-sql">
					<span class="label"><?=l('dump_bar_sql')?></span>
					<span class="text"><?=t('dump_bar_query_count', System\Query::count_all())?></span>
				</a></li>
				<li><a class="panel-status-server" href="#status-server">
					<span class="label"><?=l('dump_bar_server_vars')?></span>
					<span class="text"><?=System\Page::get_path()?></span>
				</a></li>
				<li><a class="panel-status-input" href="#status-input">
					<span class="label"><?=l('dump_bar_input_data')?></span>
					<span class="text"><?=t('dump_bar_input_data_count', count(System\Input::get()))?></span>
				</a></li>
			</ul>
		</div>
	</div>

	<div class="info">
		<div class="panel" id="status-time">
			<div class="title"><?=heading(l('dump_bar_exec_time'), true, 2)?><a class="close"><?=icon('pwf/actions/turn-off', 24)?></a></div>
			<div class="info-inner"><?=l('not_implemented')?></div>
		</div>
		<div class="panel" id="status-packages">
			<div class="title"><?=heading(l('dump_bar_packages'), true, 2)?><a class="close"><?=icon('pwf/actions/turn-off', 24)?></a></div>
			<div class="info-inner"><?=l('not_implemented')?></div>
		</div>
		<div class="panel" id="status-sql">
			<div class="title"><?=heading(l('dump_bar_sql'), true, 2)?><a class="close"><?=icon('pwf/actions/turn-off', 24)?></a></div>
			<div class="info-inner"><?=l('not_implemented')?></div>
		</div>
		<div class="panel" id="status-server">
			<div class="title"><?=heading(l('dump_bar_server_vars'), true, 2)?><a class="close"><?=icon('pwf/actions/turn-off', 24)?></a></div>
			<div class="info-inner">
				<?=dump_table($_SERVER)?>
			</div>
		</div>
		<div class="panel" id="status-input">
			<div class="title"><?=heading(l('dump_bar_input_data'), true, 2)?><a class="close"><?=icon('pwf/actions/turn-off', 24)?></a></div>
			<div class="info-inner">
				<?=dump_table(System\Input::get())?>
				<?=dump_table($_COOKIE)?>
				<?=dump_table($_SESSION)?>
			</div>
		</div>
	</div>
</div>
