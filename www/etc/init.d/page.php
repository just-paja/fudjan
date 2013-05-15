<?

/** Actions that are done on regular page init
 * @package init
 */

System\Init::basic();
session_start();

if (System\Settings::is_this_first_run()) {

	System\Setup::init();
	System\Output::out();

} else {

	System\Cache::init();
	System\Database::init();

	$request = System\Http\Request::from_hit();
	$request->init();
	$page = $request->get_page();

	if ($page) {
		if ($page->is_readable()) {

			$response = System\Http\Response::from_page($request, $page);
			$response->exec()->render();

			session_write_close();
			$response->send_headers()->display();

		} else throw new \System\Error\AccessDenied();
	} else throw new \System\Error\NotFound();
}
