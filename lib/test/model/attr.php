<?php


namespace Test\Model
{
	class Attr extends \System\Model\Attr
	{
		protected static $attrs = array(
			'int_blank' => array("type" => 'int'),
			'int_nil'   => array("type" => 'int', 'is_null' => true),
			'int_def'   => array("type" => 'int', 'default' => 5),
			'int_stat'  => array("type" => 'int', 'default' => 5, 'writeable' => false),

			'email'     => array("type" => 'email'),
			'url'       => array("type" => 'url'),
			'varchar'   => array("type" => 'varchar'),
			'file'      => array("type" => 'file'),
			'file_def'  => array("type" => 'file', "default" => 'README.md'),
		);
	}
}


namespace
{
	class Attr extends PHPUnit_Framework_TestCase
	{
		public function test_attr_existance()
		{
			$obj = new \Test\Model\Attr();

			$this->assertFalse(false, $obj->has_attr('bad'));
			$this->assertFalse(false, \Test\Model\Attr::has_attr('bad'));

			$this->assertTrue($obj->has_attr('int_nil'));
			$this->assertTrue(\Test\Model\Attr::has_attr('int_nil'));

			$this->assertTrue($obj->has_attr('int_blank'));
			$this->assertTrue($obj->has_attr('int_def'));
		}


		public function test_int_attr()
		{
			$obj = new \Test\Model\Attr();

			$this->assertNull($obj->int_nil);
			$this->assertNull($obj->int_blank);
			$this->assertEquals(5, $obj->int_def);
			$this->assertEquals(5, $obj->int_stat);

			$obj->int_blank = 'asdf';
			$obj->int_nil   = 'asdf';
			$obj->int_def   = 'asdf';

			try {
				$obj->int_stat = 1;
			} catch (\System\Error\Model $e) {
			}

			$this->assertEquals(0, $obj->int_blank);
			$this->assertNull($obj->int_nil);
			$this->assertEquals(5, $obj->int_def);
			$this->assertEquals(5, $obj->int_stat);
		}


		public function test_attr_query_has_attr()
		{
			$this->assertTrue(\Test\Model\Attr::has_attr('int_blank'));
			$this->assertTrue(\Test\Model\Attr::has_attr('int_nil'));
			$this->assertTrue(\Test\Model\Attr::has_attr('int_def'));

			$this->assertFalse(\Test\Model\Attr::has_attr('nonexistent'));

			$e = null;

			try {
				$this->assertFalse(\Test\Model\Attr::has_attr(null));
			} catch (\System\Error\Argument $e) {
			}

			$this->assertInstanceOf('System\Error\Argument', $e);
			$e = null;

			try {
				$this->assertFalse(\Test\Model\Attr::has_attr(array()));
			} catch (\System\Error\Argument $e) {
			}

			$this->assertInstanceOf('System\Error\Argument', $e);
		}


		public function test_attr_query_get_attr()
		{
			$this->assertTrue(is_array(\Test\Model\Attr::get_attr('int_blank')));
			$this->assertTrue(is_array(\Test\Model\Attr::get_attr('int_nil')));
			$this->assertTrue(is_array(\Test\Model\Attr::get_attr('int_def')));

			$e = null;

			try {
				$this->assertFalse(\Test\Model\Attr::get_attr('nonexistent'));
			} catch (\System\Error\Model $e) {
			}

			$this->assertInstanceOf('System\Error\Model', $e);
		}


		/**
		 * @dataProvider blank_items_batch
		 */
		public function test_object_conv_blank($item)
		{
			$obj = new \Test\Model\Attr($item);
			$arr = $obj->to_object();

			$this->assertTrue(!array_key_exists('int_blank', $arr));
			$this->assertTrue(!array_key_exists('int_nil', $arr));
			$this->assertTrue(!array_key_exists('email', $arr));
			$this->assertTrue(!array_key_exists('url', $arr));
			$this->assertTrue(!array_key_exists('varchar', $arr));
			$this->assertTrue(!array_key_exists('file', $arr));
			$this->assertTrue(array_key_exists('file_def', $arr));
		}


		static public function blank_items_batch()
		{
			return array(
				array(
					array(
						"email"   => null,
						"url"     => null,
						"varchar" => null,
						"file"    => null,
					)
				),

				array(
					array(
						"int_blank" => '',
						"int_nil"   => '',
						"email"     => '',
						"url"       => '',
						"varchar"   => '',
						"file"      => '',
					)
				)
			);
		}
	}
}
