<?

namespace Helper\Database\Mysqli
{
	class Column
	{
		private static $cols_no_default = array('text', 'blob');

		private static $complex_types = array(
			"image"         => array("type" => 'text'),
			"video_youtube" => array("type" => 'varchar'),
			"json"          => array("type" => 'text'),
			"int_set"       => array("type" => 'text'),
			"password"      => array("type" => 'varchar'),
			"url"           => array("type" => 'varchar'),
			"email"         => array("type" => 'varchar'),
			"bool"          => array("type" => 'tinyint', "length" => 1),
		);

		private $table;
		private $name;
		private $renamed = false;
		private $drop    = false;
		private $attr_names = array(
			'type',
			'length',
			'is_index',
			'is_null',
			'is_unique',
			'is_primary',
			'is_unsigned',
			'is_autoincrement',
			'default',
			'extra',
			'comment',
		);

		private $attrs = array();
		private $default = array();


		public function __construct(\Helper\Database\Mysqli\Table $table, $name, array $cfg = array())
		{
			$this->table = $table;
			$this->name  = $name;
			$this->use_loaded_cfg($cfg);
			$this->default = $this->attrs;
		}


		public function use_loaded_cfg($cfg)
		{
			if (any($cfg)) {
				if (strpos($cfg['Type'], '(')) {
					$t = explode(' ', $cfg['Type']);
					$t = explode('(', $t[0]);
					$this->attrs['type'] = $t[0];
					$this->attrs['length'] = intval($t[1]);
				} else $this->attrs['type'] = $cfg['Type'];

				$this->attrs['is_unsigned'] = strpos($cfg['Type'], 'unsigned') !== false;
				$this->attrs['is_unique']   = (
					strpos($cfg['Key'], 'UNI') !== false ||
					strpos($cfg['Key'], 'PRI') !== false
				);
				$this->attrs['is_primary']  = strpos($cfg['Key'], 'PRI') !== false;
				$this->attrs['is_null']     = strtolower($cfg['Null']) === 'Yes';
				$this->attrs['is_index']    = $cfg['Key'];
				$this->attrs['default']     = $cfg['Default'];
				$this->attrs['is_autoincrement'] = strpos($cfg['Extra'], "auto_increment") !== false;
				$this->attrs['extra']       = $cfg['Extra'];
				$this->attrs['name']        = $this->name;
			}
		}


		public function get_cfg()
		{
			$cfg = $this->attrs;
			$cfg['name'] = $this->name;
			return $cfg;
		}


		public function exists()
		{
			if ($this->table()->exists()) {
				$result = $this->table()->db()->query("
					SHOW COLUMNS FROM ".$this->table->name()."
					WHERE Field = '".($this->renamed ? $this->default['name']:$this->name)."'
				")->fetch();

				if (empty($this->default)) {
					$this->use_loaded_cfg($result);
					$this->default['name'] = $result['Field'];
				}

				$res = is_array($result) ? $result['Field']:$result;
				return $res == $this->name;
			}

			return false;
		}


		public function table()
		{
			return $this->table;
		}


		public function set_cfg(array $cfg)
		{
			foreach (self::$complex_types as $type=>$db_type) {
				if ($cfg['type'] == $type) {
					foreach ($db_type as $key=>$value) {
						$cfg[$key] = $value;
					}
				}
			}

			foreach ($cfg as $key=>$c)
			{
				if (in_array($key, $this->attr_names)) {
					$this->attrs[$key] = $c;
				}
			}

			if ($this->attrs['type'] == 'varchar' && !isset($this->attrs['length'])) {
				$this->attrs['length'] = 255;
			}

			if ($this->attrs['type'] == 'text' && !isset($this->attrs['length'])) {
				$this->attrs['length'] = 65535;
			}
		}


		public function rename($name)
		{
			$this->renamed = true;
			$this->default['name'] = $this->name;
			$this->name = $name;
			return $this;
		}


		public function drop()
		{
			$this->drop = true;
		}


		public function save()
		{
			$query =
				"ALTER TABLE `".$this->table()->db()->name()."`.`".$this->table->name()."` ".
				$this->get_save_query().';';

			if ($this->renamed) {
				v($query);
			}

			$this->table()->db()->query($query);
			return $this;
		}


		public function get_save_query()
		{
			if ($this->drop) {
				if ($this->exists()) {
				return "DROP `".$this->name."`";
				} else throw new \System\Error\Database(sprintf('Column %s cannot be dropped from table %s. It does not exists.', $this->name, $this->table()->name()));
			} else {
				$exists = $this->exists();

				if ($exists || $this->renamed) {
					$front = 'CHANGE `'.$this->default['name'].'` `'.$this->name.'`';
				} elseif ($this->table()->exists()) {
					$front = 'ADD `'.$this->name.'`';
				} else {
					$front = '`'.$this->name.'`';
				}

				if (isset($this->attrs['default'])) {
					$defval = $this->attrs['default'];

					if (strpos($this->attrs['type'], 'int')) {
						$defval = intval($defval);
					}

					if ($defval != 'NOW()' && !is_numeric($defval)) {
						$defval = "'".$defval."'";
					}
				}

				$sq = implode(' ', array(
					$front,
					$this->attrs['type'].(any($this->attrs['length']) ? '('.$this->attrs['length'].')':''),
					any($this->attrs['is_unsigned']) ? 'unsigned':'',
					any($this->attrs['is_null']) && !isset($this->attrs['default']) ? 'NULL':'NOT NULL',
					isset($this->attrs['default']) && !in_array($this->attrs['type'], self::$cols_no_default) ? 'DEFAULT '.$defval.'':'',
					any($this->attrs['is_autoincrement']) ? 'AUTO_INCREMENT':'',
					any($this->attrs['is_unique']) && empty($this->attrs['is_primary']) && empty($this->default['is_unique']) ? 'UNIQUE':'',
					any($this->attrs['is_primary']) && empty($this->default['is_primary']) ? 'PRIMARY KEY':'',
					any($this->attrs['comment']) ? " COMMENT '".$this->attrs['comment']."'":'',
				));

				return trim($sq);
			}
		}
	}
}
