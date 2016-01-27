<?php

class Database_Parser_Test extends Test_Unit {

	function data_provider_split_order_by() {
		return array(
			// Suffix, string
			array(
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC, SOME_FUNCTION(CURDATE(),'2014-12-30') DESC",
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC"
				),
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC, SOME_FUNCTION(CURDATE(),'2014-12-30') ASC"
			),
			// Suffix, array
			array(
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC"
				),
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC"
				),
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') ASC"
				)
			),
			// No suffix, string
			array(
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365), SOME_FUNCTION(CURDATE(),'2014-12-30')",
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)",
					"SOME_FUNCTION(CURDATE(),'2014-12-30')"
				),
				"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC, SOME_FUNCTION(CURDATE(),'2014-12-30') DESC"
			),
			// No suffix, array
			array(
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)",
					"SOME_FUNCTION(CURDATE(),'2014-12-30')"
				),
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)",
					"SOME_FUNCTION(CURDATE(),'2014-12-30')"
				),
				array(
					"((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC",
					"SOME_FUNCTION(CURDATE(),'2014-12-30') DESC"
				)
			),
			// Random tests
			array(
				"IF(A=B,0,1),ColumnName,'some string',IF(CURDATE()<modified,'2014-12-01',modified)",
				array(
					"IF(A=B,0,1)",
					"ColumnName",
					"'some string'",
					"IF(CURDATE()<modified,'2014-12-01',modified)"
				),
				"IF(A=B,0,1) DESC, ColumnName DESC, 'some string' DESC, IF(CURDATE()<modified,'2014-12-01',modified) DESC"
			)
		);
	}

	/**
	 * @data_provider data_provider_split_order_by
	 */
	function test_split_order_by($order_by, $expected_split, $expected_reverse) {
		$parser = Database::instance()->parser();

		$actual = $parser->split_order_by($order_by);

		$this->assert_equal_array($actual, $expected_split);

		$actual_reverse = $parser->reverse_order_by($order_by);

		// 		$this->log('reverse_order_by');
		// 		$this->log($actual_reverse);
		// 		$this->log($expected_reverse);
		$this->assert_equal($actual_reverse, $expected_reverse);
	}
}