<?php

namespace Test\Database
{
	class All extends \PHPUnit_Framework_TestCase
	{
		/**
		 * @dataProvider query_batch
		 */
		public function test_filter_query_conds($conds, $result)
		{
			$q = new \System\Database\Query();
			$q->add_filter($conds);

			$test = $q->get_query_conds();
			$this->assertEquals($test, $result);
		}



		public static function query_batch()
		{
			return array(
				array(
					array(),
					''
				),
				array(
					array(
						"attr" => 'test',
						'type' => 'exact',
						'exact' => 1
					),
					' WHERE `test` = \'1\''
				),
				array(
					array(
						'attr' => 'test',
						'type' => 'gt',
						'gt' => 1
					),
					' WHERE `test` > 1'
				),
				array(
					array(
						'attr' => 'test',
						'type' => 'gte',
						'gte' => 1
					),
					' WHERE `test` >= 1'
				),
				array(
					array(
						'attr' => 'test',
						'type' => 'lt',
						'lt' => 1
					),
					' WHERE `test` < 1'
				),
				array(
					array(
						'attr' => 'test',
						'type' => 'lte',
						'lte' => 1
					),
					' WHERE `test` <= 1'
				),
				array(
					array(
						'attr' => 'test',
						'type' => 'exact',
						'exact' => array(1, 2)
					),
					' WHERE `test` IN (1,2)'
				),
				array(
					array(
						'type' => 'or',
						'or' => array(
							array(
								'attr' => 'test',
								'type' => 'exact',
								'exact' => 1
							),
							array(
								'attr' => 'test',
								'type' => 'exact',
								'exact' => 2
							)
						)
					),
					' WHERE (`test` = \'1\' OR `test` = \'2\')'
				),
				array(
					array(
						'type' => 'and',
						'and' => array(
							array(
								'attr' => 'test',
								'type' => 'exact',
								'exact' => 1
							),
							array(
								'attr' => 'test',
								'type' => 'exact',
								'exact' => 2
							)
						)
					),
					' WHERE `test` = \'1\' AND `test` = \'2\''
				),
				array(
					array(
						'type' => 'or',
						'or' => array(
							array(
								'type' => 'and',
								'and' => array(
									array(
										'attr' => 'test',
										'type' => 'exact',
										'exact' => 1
									),
									array(
										'attr' => 'test',
										'type' => 'exact',
										'exact' => 2
									)
								)
							)
						)
					),
					' WHERE (`test` = \'1\' AND `test` = \'2\')'
				),
				array(
					array(
						'type' => 'or',
						'or' => array(
							array(
								'type' => 'and',
								'and' => array(
									array(
										'attr' => 'testa',
										'type' => 'exact',
										'exact' => 1
									),
									array(
										'attr' => 'testb',
										'type' => 'exact',
										'exact' => 2
									)
								)
							),
							array(
								'type' => 'and',
								'and' => array(
									array(
										'attr' => 'testa',
										'type' => 'exact',
										'exact' => 3
									),
									array(
										'attr' => 'testb',
										'type' => 'exact',
										'exact' => 4
									)
								)
							)
						)
					),
					' WHERE ((`testa` = \'1\' AND `testb` = \'2\') OR (`testa` = \'3\' AND `testb` = \'4\'))'
				),
			);
		}
	}
}
