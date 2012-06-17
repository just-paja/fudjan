<?

namespace System
{
	class Migration extends Model\Basic
	{

		const BASEDIR = '/etc/database/migrations.d';
		static protected $id_col = 'id_database_migration';
		static protected $table = 'database_migration';
		static protected $required_attrs = array();
		static protected $attrs = array(
			"string" => array('seoname','name','desc','md5_sum','status'),
			"datetime" => array('created_at','updated_at','date')
		);

		static function get_new()
		{
			try {
				$old_items = get_all("System\Migration")->fetch();
			} catch (Exception $e) {
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
				} catch (Exception $e) 	{
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
			$dir = opendir(ROOT.self::BASEDIR);
			$items = array();
			while ($file = readdir($dir)) {
				if (preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-[a-zA-Z\_\-]*\.php$/", $file)) {
					$fname = explode('-', $file);
					$date = new \Datetime(intval($fname[0]).'-'.intval($fname[1]).'-'.intval($fname[2]));
					$name = \System\File::remove_postfix($fname[3]);

					if (!in_array(format_time("sql-date", $date).'-'.$name, $old)) {
						$temp = &$items[];
						$temp = new self(array(
							"file"    => $file,
							"date"    => $date,
							"seoname" => $name,
							"status"  => 'new',
						));
						$temp->get_meta();
						$temp->get_checksum();
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
			include($p = ROOT.self::BASEDIR.'/'.$this->get_filename());
			$this->sql("COMMIT");

			$this->get_checksum();
			$this->status = empty($this->errors) ? 'ok':'failed';
			$this->save();

			return $this;
		}


		private function get_checksum()
		{
			if (!$this->md5_sum) {
				$this->md5_sum = md5(file_get_contents($p = ROOT.self::BASEDIR.'/'.$this->get_filename()));
			}
			return $this->md5_sum;
		}


		private function sql($query)
		{
			try {
				return Database::query($query);
			} catch (Exception $e) {
				$this->status = 'failed';
				$this->errors[] = $e->getMessage();
			}
		}


		public function get_filename()
		{
			return format_time("sql-date", $this->date).'-'.$this->seoname.'.php';
		}


		public function get_meta()
		{
			if (file_exists($p = ROOT.self::BASEDIR.'/'.$this->get_filename())) {
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
