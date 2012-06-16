<?

$page = System\Page::fetch_page();

if (!($page instanceof System\Page)) {
	System\Status::recoverable_error(404);
}

if (!$page->is_readable()) {
	System\Status::recoverable_error(403);
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

