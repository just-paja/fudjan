<?

/** Actions that are done on regular page init
 * @package init
 */

System\Init::basic();

if (System\Settings::is_this_first_run()) {

	System\Setup::init();
	System\Output::init();
	System\Output::set_format('html');
	System\Output::out();

} else {

	System\Cache::init();
	System\Database::init();
	System\Output::init();

	$request = System\Http\Request::from_hit();
	$page = $request->get_page();

	if ($page) {
		if ($page->is_readable()) {

			$request->init();
			$page->add_modules();
			$response = System\Http\Response::from_page($request, $page);

			if (cfg('dev', 'debug')) {
				System\Init::low_level_devel($response);
			}

			System\Flow::run($response);

			$response->render()->send_headers()->display();


		} else throw new \System\Error\AccessDenied();
	} else throw new \System\Error\NotFound();
}
