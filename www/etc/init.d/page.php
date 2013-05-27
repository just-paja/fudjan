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
	$response = $request->create_response();

	if ($response) {
		if ($response->is_readable()) {

			$response->exec()->render()->send_headers()->send_content();

		} else throw new \System\Error\AccessDenied();
	} else throw new \System\Error\NotFound();
}
