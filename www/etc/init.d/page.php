<?

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

		if (cfg('dev', 'debug')) {
			System\Init::low_level_devel();
		}

		if (!(($page = System\Page::get_current()) instanceof System\Page)) {
			throw new \System\Error\NotFound();
		}

		if (!$page->is_readable()) {
			throw new \System\Error\AccessDenied();
		}

		content_for("meta", $page->get_meta());
		System\Output::set_opts(array(
			"format"   => cfg("output", 'format_default'),
			"lang"     => System\Locales::get_lang(),
			"title"    => $page->title,
			"template" => $page->template,
			"page"     => $page->seoname,
		));

		System\Flow::run();
		System\Flow::run_messages();

		if (strpos($page->get_path(), "/cron") === 0) {
			System\Status::report("cron", "Requested cron page ".$page->get_path());
		}

		System\Output::out();
		System\Message::dequeue_all();

	} else {

		System\Setup::init();
		System\Setup::set_step('no_pages');
		System\Output::init();
		System\Output::set_format('html');
		System\Output::out();

	}
}
