<?


namespace Test\Model\Mock
{
	class Database extends \System\Model\Database
	{
		protected static $attrs = array(
			'int_blank' => array("type" => 'int'),
			'int_nil' => array("type" => 'int', 'is_null' => true),
			'int_def' => array("type" => 'int', 'default' => 5),
			'belongs' => array("type" => self::REL_BELONGS_TO, "model" => 'Test\Model\Mock\Database'),
			'belongs_def' => array("type" => self::REL_BELONGS_TO, "model" => 'Test\Model\Mock\Database', "default" => 1),
			'belongs_null' => array("type" => self::REL_BELONGS_TO, "is_null" => true, "model" => 'Test\Model\Mock\Database'),

			'has_one' => array("type" => 'has_one', "is_null" => true, "model" => 'Test\Model\Mock\Database'),
			'file' => array("type" => 'file', "is_null" => true),
		);


		public function save()
		{
			$this->id = 1;
			return $this;
		}


		public function get_rel_belongs_to($rel)
		{
			$idc = static::get_belongs_to_id($rel);
			$def = static::get_attr($rel);

			if ($this->$idc) {
				return new $def['model'](array(
				'id' => $this->data[$idc]
				));
			}

			return null;
		}


		protected function set_rel_single_value($name, $value)
		{
			$idc = static::get_belongs_to_id($name);
			$def = static::get_attr($name);

			if (is_numeric($value)) {
				$value = new $def['model'](array(
					'id' => $value
				));

				$this->relations[$name] = $value;
				$this->$idc = $value->id;
			}
		}
	}
}


namespace Test\Model
{
	class Database extends \PHPUnit_Framework_TestCase
	{
		public function test_lifecycle()
		{
			$obj = new \Test\Model\Mock\Database();

			$this->assertTrue($obj->is_new());
			$obj->save();
			$this->assertFalse($obj->is_new());
		}


		/**
		 * @dataProvider database_items_batch
		 */
		public function test_raw_data($item)
		{
			$f = BASE_DIR.'/var/cache/test/file.test';

			\System\Directory::check(BASE_DIR.'/var/cache/test');
			file_put_contents($f, 'test');

			$obj = new \Test\Model\Mock\Database($item);
			$data = $obj->get_data_raw();

			if ($obj->belongs) {
				$this->assertFalse(array_key_exists('belongs', $data));
				$this->assertTrue(array_key_exists('id_belongs', $data));
			}

			if ($obj->belongs_def) {
				$this->assertFalse(array_key_exists('belongs', $data));
				$this->assertTrue(array_key_exists('id_belongs_def', $data));
			}

			if ($obj->belongs_null) {
				$this->assertFalse(array_key_exists('belongs_null', $data));
				$this->assertTrue(array_key_exists('id_belongs_null', $data));
			}

			if ($obj->file) {
				$this->assertTrue(array_key_exists('file', $data));

				$this->assertTrue(is_string($data['file']));

				$file = json_decode($data['file'], true);

				$this->assertTrue(array_key_exists('hash', $file));
				$this->assertEquals($obj->file->hash, $file['hash']);
			}

			unlink($f);
		}



		public static function database_items_batch()
		{
			return array(
				array(
					array(
						"int_blank" => 1,
						"int_nil" => 1,
						"int_def" => 1,
						"id_belongs" => 1,
						"file" => BASE_DIR.'/var/cache/test/file.test'
					),
				),

				array(
					array(
						"belongs" => 1,
						"file" => null,
					)
				),

				array(
					array(
						"file" => array(
							"path"  => BASE_DIR.'/var/cache/test/file.test',
							"hash"  => 'b09d56139810ab6f2be0fadfbb171472',
							"suff"  => 'test',
							"name"  => 'file',
							"saved" => true,
							"size"  => 10
						),
					)
				)
			);
		}
	}
}
