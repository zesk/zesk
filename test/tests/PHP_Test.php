<?php

namespace zesk;

class PHP_Test extends Test_Unit {
	function test_php_basics() {
		$this->assert_false(!!array());
		$this->assert_true(!!array(
			1
		));
		$truthy = array(
			new \stdClass(),
			array(
				1
			),
			array(
				""
			),
			array(
				0
			),
			array(
				null
			)
		);
		$falsy = array(
			0,
			"",
			null,
			false,
			0.0
		);
		foreach ($truthy as $true) {
			$this->assert(!!$true, gettype($true) . " is not TRUE " . var_export($true, true));
		}
		foreach ($falsy as $false) {
			$this->assert(!$false, gettype($false) . " is not FALSE " . var_export($false, true));
		}
	}
	
	/**
	 * PHP does not support Javascript-style assignment using ||, e.g.
	 *
	 * JS: var a = arg || {};
	 */
	function test_php_andor() {
		$a = new \stdClass();
		$a->val = "a";
		$b = new \stdClass();
		$b->val = "b";
		
		$c = $a || $b;
		$this->assert_equal($c, true);
		
		$c = $a || array();
		$this->assert_equal($c, true);
		
		$c = false || array();
		$this->assert_equal($c, false);
	}
	function test_php_references() {
		$bigthing = array(
			"a" => array(
				"kind" => "letter",
				"code" => 65
			),
			"b" => array(
				"kind" => "letter",
				"code" => 66
			),
			"9" => array(
				"kind" => "number",
				"code" => ord('9')
			)
		);
		
		$otherarray = array();
		$otherarray["test"] = & $bigthing['a'];
		// What happens to $bigthing?
		unset($otherarray["test"]);
		// Nothing, unset applies only to the key in the array
		
		$this->assert_arrays_equal($bigthing, array(
			"a" => array(
				"kind" => "letter",
				"code" => 65
			),
			"b" => array(
				"kind" => "letter",
				"code" => 66
			),
			"9" => array(
				"kind" => "number",
				"code" => ord('9')
			)
		));
	}
}