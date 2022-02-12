<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Parser_Test extends Test_Unit {
	protected array $load_modules = [
		"MySQL",
	];

	public function data_provider_split_order_by() {
		return [
			// Suffix, string
			[
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC, SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
				],
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC, SOME_FUNCTION(CURDATE(),'2014-12-30') ASC",
			],
			// Suffix, array
			[
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
				],
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
				],
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') ASC",
				],
			],
			// No suffix, string
			[
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365), SOME_FUNCTION(CURDATE(),'2014-12-30')",
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)",
					"SOME_FUNCTION(CURDATE(),'2014-12-30')",
				],
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC, SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
			],
			// No suffix, array
			[
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)",
					"SOME_FUNCTION(CURDATE(),'2014-12-30')",
				],
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)",
					"SOME_FUNCTION(CURDATE(),'2014-12-30')",
				],
				[
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
				],
			],
			// Random tests
			[
				"IF(A=B,0,1),ColumnName,'some string',IF(CURDATE()<modified,'2014-12-01',modified)",
				[
					"IF(A=B,0,1)",
					"ColumnName",
					"'some string'",
					"IF(CURDATE()<modified,'2014-12-01',modified)",
				],
				"IF(A=B,0,1) DESC, ColumnName DESC, 'some string' DESC, IF(CURDATE()<modified,'2014-12-01',modified) DESC",
			],
		];
	}

	/**
	 * @data_provider data_provider_split_order_by
	 */
	public function test_split_order_by($order_by, $expected_split, $expected_reverse): void {
		$parser = $this->application->database_registry()->parser();

		if (is_array($order_by)) {
			$actual = [];
			foreach ($order_by as $k => $v) {
				$actual[$k] = $parser->split_order_by($v);
			}
		} else {
			$actual = $parser->split_order_by($order_by);
		}

		$this->assert_equal_array($actual, $expected_split);

		$actual_reverse = $parser->reverse_order_by($order_by);

		// 		$this->log('reverse_order_by');
		// 		$this->log($actual_reverse);
		// 		$this->log($expected_reverse);
		$this->assert_equal($actual_reverse, $expected_reverse);
	}
}
