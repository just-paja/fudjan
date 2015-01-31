<?

namespace System\Database
{
	class Migration extends \System\Model\Database
	{

		const DIR = '/etc/database/migrations.d';
		static protected $attrs = array(
			"seoname" => array('varchar'),
			"name"    => array('varchar'),
			"desc"    => array('varchar'),
			"md5_sum" => array('varchar'),
			"status"  => array('varchar'),
			"date"    => array('datetime'),
		);

		static function get_new()
		{
			try {
				$old_items = get_all("System\Migration")->fetch();
			} catch (\System\Error $e) {
				$old_items = array();
			}

			$old = array();
			foreach ($old_items as &$m) {
				$old[] = $m->date->format('sql').'-'.$m->seoname;
			}

			$items = self::checkout_folder($old);

			if (any($items)) {
				$sums = collect(array('attr', 'md5_sum'), $items, true);
				try {
					$old = get_all("\System\Migration", array("t0.md5_sum IN ('".implode("','", $sums)."')"))->fetch();
				} catch (\System\Error $e) {
					$old = array();
				}
			}

			foreach ($old as $mig) {
				foreach ($items as $key=>$nmig) {
					if ($mig->get_checksum() == $nmig->get_checksum()) {
						if ($mig->status == 'ok') {
							unset($items[$key]);
						} else {
							$items[$key] = $mig;
						}
					}
				}
			}

			uasort($items, array('self', 'sort'));
			return $items;
		}


		/* Get all migrations from the migrations folder
		 * @return array Set of migrations
		 */
		public static function checkout_folder(array $old = array())
		{
			$files = \System\Composer::list_files(self::DIR);
			$items = array();

			foreach ($files as $file_path) {
				$file = basename($file_path);

				if (preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[a-zA-Z\_\-]*\.php$/", $file)) {
					$fname = explode('-', $file);
					$date = new \Datetime(intval($fname[0]).'-'.intval($fname[1]).'-'.intval($fname[2]));

					$name = explode('.', $fname[3]);
					array_pop($name);
					$name = implode('.', $name);

					if (!in_array($date->format("Y-m-d").'-'.$name, $old)) {
						$temp = new self(array(
							"file"    => $file,
							"date"    => $date,
							"seoname" => $name,
							"status"  => 'new',
						));

						$temp->get_meta();
						$temp->get_checksum();

						$items[] = $temp;
					}
				}
			}

			usort($items, array("self", "sort"));
			return $items;
		}


		static function run_all(&$items = null)
		{
			if (!$items) {
				$items = self::get_new();
			}

			foreach ($items as &$m) {
				if ($m->run()->status != 'ok') {
					return false;
				}
			}

			return true;
		}



		public function &run()
		{
			$this->sql("START TRANSACTION");
			include($this->get_file_path());
			$this->sql("COMMIT");

			$this->get_checksum();
			$this->status = empty($this->errors) ? 'ok':'failed';
			$this->save();

			return $this;
		}


		private function get_checksum()
		{
			if (!$this->md5_sum) {
				$this->md5_sum = md5(\System\File::read($p = $this->get_file_path()));
			}
			return $this->md5_sum;
		}


		private function sql($query)
		{
			try {
				return \System\Database::query($query);
			} catch (\System\Error $e) {
				$this->status = 'failed';
				$this->errors[] = $e->getMessage();
			}
		}


		public function get_filename()
		{
			return $this->date->format('Y-m-d').'-'.$this->seoname.'.php';
		}


		public function get_file_path()
		{
			return \System\Composer::resolve(self::DIR.'/'.$this->get_filename());
		}


		public function get_meta()
		{
			if (file_exists($p = BASE_DIR.self::DIR.'/'.$this->get_filename())) {
				$c = file($p);
				foreach($c as $line) {
					if (strpos($line, '#[') === 0) {
						$this->name = self::cut_meta($line);
						if (strpos(next($c), '#[') === 0) {
							$this->desc = self::cut_meta($line);
						}
						break;
					}
				}
			}
		}


		static function cut_meta($line)
		{
			return substr($line, 2, strlen($line)-4);
		}


		private static function sort(self $a, self $b)
		{
			if (($ta = $a->date->getTimestamp()) == ($tb = $b->date->getTimestamp())) {
				return 0;
			}
			return ($ta < $tb) ? -1:1;

		}
	}
}
