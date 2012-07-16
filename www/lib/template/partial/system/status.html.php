<div class="status-dump">
	<h3>Stystem status</h3>
	<div class="pre">
		<strong>Page path:</strong> <?=System\Page::get_path()?><br />
		<strong>SQL Queries:</strong> <?=System\Query::count_all()?><br />
		<strong>Exec time:</strong> <?=round(System\Flow::get_exec_time(), 6)?>s
	</div>
</div>
