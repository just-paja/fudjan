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

echo div('status devbar');
	echo div('status-dump',
		div('status-dump-inner',
			ul('bar-menu plain', array(
				li($ren->link('', span('label', l('dump_bar_hide')), array("class" => 'close'))),
				li($ren->link(
					'#status-time',
					array(span('label', l('dump_bar_exec_time')), span("text", t('dump_bar_exec_time_val', number_format($flow->get_exec_time(), 9)))),
					array("class" => 'panel-status-time')
				)),
				li($ren->link(
					'#status-packages',
					array(span('label', l('dump_bar_packages')), span("text", introduce())),
					array("class" => 'panel-status-packages')
				)),
				li($ren->link(
					'#status-sql',
					array(span('label', l('dump_bar_sql')), span("text", t('dump_bar_query_count', System\Database\Query::count_all()))),
					array("class" => 'panel-status-sql')
				)),
				li($ren->link(
					'#status-server',
					array(span('label', l('dump_bar_server_vars')), span("text", $request->path)),
					array("class" => 'panel-status-server')
				)),
				li($ren->link(
					'#status-input',
					array(span('label', l('dump_bar_input_data')), span("text", t('dump_bar_input_data_count', count(0)))),
					array("class" => 'panel-status-input')
				)),
				li($ren->link(
					'#status-input',
					array(span('label', l('dump_bar_output')), span("text", t('dump_bar_template_count', count(0)))),
					array("class" => 'panel-status-output')
				))
			))
		)
	);

	echo div('info');

		echo div('panel', array(
				div('title', array(
					$ren->heading_static(l('dump_bar_exec_time'), 2),
					$ren->icon_for('#', 'pwf/actions/turn-off', 24, array("class" => 'close'))
				)),
				div('info-inner',
					div('info-padding', dump_table(array(
						l('dump_bar_time_flow') => number_format($flow->get_exec_time(), 12),
						l('dump_bar_time_renderer') => number_format($ren->get_exec_time(), 12),
						l('dump_bar_time_response') => number_format($response->get_exec_time(), 12),
					)))
				),
			), 'status-time');


		echo div('panel', array(
				div('title', array(
					$ren->heading_static(l('dump_bar_packages')),
					$ren->icon_for('#', 'pwf/actions/turn-off', 24, array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', l('not_implemented'))),
			), 'status-packages');


		echo div('panel', null, 'status-sql');
			echo div('title', array(
				$ren->heading_static(l('dump_bar_packages')),
				$ren->icon_for('#', 'pwf/actions/turn-off', 24, array("class" => 'close'))
			));
			echo div(array('info-inner', 'sql'));
				echo div('info-padding');

				$total = 0.0;

				echo ul('plain');

					foreach (\System\Database::get_query_record() as $q) {
						Tag::li(array("content" => array(
								div('info', array(
									div('file', t('dump_query_file', $q['trace']['file'], $q['trace']['line'])),
									div('time', t('dump_query_execution_time', number_format($q['time'], 9))),
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

				echo div('total', t('dump_query_total_execution_time', number_format($total, 9)));
			close('div');
		close('div');
		close('div');

		echo div('panel', array(
				div('title', array(
					$ren->heading_static(l('dump_bar_server_vars')),
					$ren->icon_for('#', 'pwf/actions/turn-off', 24, array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', dump_table($_SERVER))),
			), 'status-server');


		echo div('panel', array(
				div('title', array(
					$ren->heading(l('dump_bar_input_data'), 2),
					$ren->icon_for('#', 'pwf/actions/turn-off', 24, array("class" => 'close'))
				)),
				div('info-inner', div('info-padding', array(
					div('datadump', array(
						$ren->heading_static(l('dump_bar_input_data_get')),
						dump_table($request->get),
					)),

					div('datadump', array(
						$ren->heading_static(l('dump_bar_input_data_post')),
						dump_table($request->post),
					)),

					div('datadump', array(
						$ren->heading_static(l('dump_bar_input_data_cookies')),
						dump_table($_COOKIE),
					)),

					div('datadump', array(
						$ren->heading_static(l('dump_bar_input_data_session')),
						dump_table($_SESSION),
					)),
				))),
			), 'status-input');


		echo div('panel', null, 'status-output');
			echo div('title', array(
				$ren->heading_static(l('dump_bar_output'), 2),
				$ren->icon_for('#', 'pwf/actions/turn-off', 24, array("class" => 'close'))
			));

			echo div('info-inner');
				echo div('info-padding');

					//~ $data = \System\Output::get_template_data();

					//~ foreach ($data as $row) {
						//~ echo div('datadump', array(
							//~ heading("'".$row['name']."'".' ('.$row['type'].')', true, 3),
							//~ any($row['locals']) ? dump_table($row['locals']):l('dump_bar_no_locals'),
						//~ ));
					//~ }

				close('div');
			close('div');
		close('div');
	close('div');
close('div');
