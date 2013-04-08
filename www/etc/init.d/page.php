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

	if (System\Settings::is_page_tree_ready()) {

		System\Cache::init();
		System\Database::init();
		System\Output::init();
		System\Page::init();

		content_for('scripts', 'lib/functions');
		content_for('scripts', 'lib/jquery');
		content_for('scripts', 'pwf');
		content_for('styles', 'pwf/elementary');

		foreach (cfg('site', 'init') as $init_step) {
			if (file_exists($f = ROOT.'/etc/init.d/'.$init_step.'.php')) {
				require_once($f);
			}
		}

		if (cfg('dev', 'debug')) {
			System\Init::low_level_devel();
		}

		if (!(($page = System\Page::get_current()) instanceof System\Page)) {
			throw new \System\Error\NotFound();
		}

		if (!$page->is_readable()) {
			throw new \System\Error\AccessDenied();
		}

		System\Output::set_opts(array(
			"format"   => cfg("output", 'format_default'),
			"lang"     => System\Locales::get_lang(),
			"title"    => $page->title,
			"template" => $page->template,
			"page"     => $page->seoname,
		));

		foreach ($page->get_meta() as $meta) {
			content_for("meta", $meta);
		}

		System\Flow::run();
		System\Flow::run_messages();

		if (strpos($page->get_path(), "/cron") === 0) {
			System\Status::report("cron", "Requested cron page ".$page->get_path());
		}

		System\Output::out();
		System\Message::dequeue_all();

	} else throw new \System\Error\Config('There are no routes.', sprintf('Add some routes to the pages file located in "%s".', ROOT.\System\Settings::DIR_CONF_ALL));
}
