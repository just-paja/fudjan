<?

namespace System
{
	class Mailer
	{
		const DIR_TEMPLATE = "/lib/template/mailer";

		public static function get_all_templates($type = null)
		{
			$templates = array();
			$dir = opendir($p = ROOT.self::DIR_TEMPLATE);

			while ($f = readdir($dir)) {
				if ((is_file($p.'/'.$f) && (strpos($f, ".html") || strpos($f, ".txt"))) && (!$type || ($type && strpos($f, ".".$type.".")))) {
					$templates[] = $f;
				}
			}

			return $templates;
		}


		private static function get_run_stack()
		{
			return get_all("Core\Mailer\QueueItem", array("status" => 'ready'), array("order-by" => 'created_at ASC', "limit" => 256))->fetch();
		}


		// send all queued messages
		public static function run_queue()
		{
			$status = array();
			if (true || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || user()->has_right('run-cron')) {
				if (!System\Settings::get('offcom', 'disable')) {
					$items = self::get_run_stack();

					while (any($items)) {

						foreach ($items as &$item) {
							$status[$item->id] = $item->send();
						}

						break;
					}

					message('success', _('Rozesílání fronty zpráv'), _('Rozesílání e-mailové fronty dokončeno'));
				} else message('error', _('Rozesílání fronty zpráv'), _('Síťová komunikace je zakázána'));
			} else message('error', _('Rozesílání fronty zpráv'), _('Nemáte oprávnění k rozesílání e-mailové fronty'));

			return $status;
		}
	}
}
