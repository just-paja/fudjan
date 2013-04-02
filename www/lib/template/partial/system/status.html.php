<?

if (!defined('H_STATUS_DUMP')) {
	define('H_STATUS_DUMP', true);

	function dump_table(array $values)
	{
		$str = '';
		$str .= Stag::table();
			$str .= Stag::thead(array("content" => Stag::tr(array("content" => array(
				Stag::th(array("content" => l('dump_bar_key'))),
				Stag::th(array("content" => l('dump_bar_value'))),
			)))));
			$str .= Stag::tbody();
				foreach ($values as $key=>$value) {
					if (is_array($value)) {
						$value = var_export($value, true);
					}

					if (is_object($value)) {
						$value = 'Instance of '.get_class($value);
					}

					if (is_null($value)) {
						$value = 'NULL';
					}

					$str .= Stag::tr(array("content" => array(
						Stag::td(array("content" => $key)),
						Stag::td(array("content" => $value)),
					)));
				}
			$str .= Stag::close('tbody');
		$str .= Tag::close('table');

		return $str;
	}
}

?>
<div class="status devbar">
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
					<span class="text"><?=t('dump_bar_query_count', System\Database\Query::count_all())?></span>
				</a></li>
				<li><a class="panel-status-server" href="#status-server">
					<span class="label"><?=l('dump_bar_server_vars')?></span>
					<span class="text"><?=System\Page::get_path()?></span>
				</a></li>
				<li><a class="panel-status-input" href="#status-input">
					<span class="label"><?=l('dump_bar_input_data')?></span>
					<span class="text"><?=t('dump_bar_input_data_count', count(System\Input::get()))?></span>
				</a></li>
				<li><a class="panel-status-output" href="#status-output">
					<span class="label"><?=l('dump_bar_output')?></span>
					<span class="text"><?=t('dump_bar_template_count', count(System\Output::count_templates()))?></span>
				</a></li>
			</ul>
		</div>
	</div>

	<div class="info">
		<?

		echo div('panel', array(
				div('title', array(
					heading(l('dump_bar_exec_time'), true, 2),
					link_for(icon('pwf/actions/turn-off', 24), '#', array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', l('not_implemented'))),
			), 'status-time');


		echo div('panel', array(
				div('title', array(
					heading(l('dump_bar_packages'), true, 2),
					link_for(icon('pwf/actions/turn-off', 24), '#', array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', l('not_implemented'))),
			), 'status-packages');


		echo div('panel', null, 'status-sql');
			echo div('title', array(
				heading(l('dump_bar_packages'), true, 2),
				link_for(icon('pwf/actions/turn-off', 24), '#', array("class" => 'close'))
			));
			echo div(array('info-inner', 'sql'));
				echo div('info-padding');

				$total = 0.0;

				echo ul('plain');

					foreach (\System\Database::get_query_record() as $q) {
						Tag::li(array("content" => array(
								div('info', array(
									div('file', t('dump_query_file', $q['trace']['file'], $q['trace']['line'])),
									div('time', t('dump_query_execution_time', round($q['time'], 9))),
								)),
								Stag::pre(array("content" =>
									str_replace(
										array(',', ' FROM', 'SELECT '),
										array(',<br>  ', 'FROM', 'SELECT<br>  '),
									$q['query']))),
							)
						));

						$total += $q['time'];
					}

				close('ul');

				echo div('total', t('dump_query_total_execution_time', round($total, 9)));
			close('div');
		close('div');
		close('div');

		echo div('panel', array(
				div('title', array(
					heading(l('dump_bar_server_vars'), true, 2),
					link_for(icon('pwf/actions/turn-off', 24), '#', array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', dump_table($_SERVER))),
			), 'status-server');


		echo div('panel', array(
				div('title', array(
					heading(l('dump_bar_input_data'), true, 2),
					link_for(icon('pwf/actions/turn-off', 24), '#', array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', array(
					div('datadump', array(
						heading(l('dump_bar_input_data_get_post'), true, 3),
						dump_table(System\Input::get()),
					)),

					div('datadump', array(
						heading(l('dump_bar_input_data_cookies'), true, 3),
						dump_table($_COOKIE),
					)),

					div('datadump', array(
						heading(l('dump_bar_input_data_session'), true, 3),
						dump_table($_SESSION),
					)),
				))),
			), 'status-input');


		echo div('panel', null, 'status-output');
			echo div('title', array(
				heading(l('dump_bar_output'), true, 2),
				link_for(icon('pwf/actions/turn-off', 24), '#', array("class" => 'close'))
			));

			echo div('info-inner');
				echo div('info-padding');

					$data = \System\Output::get_template_data();

					foreach ($data as $row) {
						echo div('datadump', array(
							heading($row['name'].' ('.$row['type'].')', true, 3),
							any($row['locals']) ? dump_table($row['locals']):l('dump_bar_no_locals'),
						));
					}

				close('div');
			close('div');
		close('div');

		?>
	</div>
</div>
