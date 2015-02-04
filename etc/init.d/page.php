<?

/** Actions that are done on regular page init
 * @package init
 */

System\Init::basic();
session_start();

$request = System\Http\Request::from_hit();
$request->init();

System\Cache::init();
System\Database::init();

$response = $request->create_response();

if ($response) {
	$response->init();

	if ($response->is_readable()) {

		try {
			$response->locales->load_messages();
		} catch (\System\Error\Locales $e) {
			$err = new \System\Error\SeeOther();
			$err->location = $request->path .'?lang=cs';
			throw $err;
		}

		$response
			->exec()
			->render()
			->send_headers()
			->send_content();

	} else throw new \System\Error\AccessDenied();
} else throw new \System\Error\NotFound();
