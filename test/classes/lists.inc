<?php
class test_lists extends Test_Unit {
	function append_data_provider() {
		$lists = array(
			array(
				"a",
				"b",
				"c",
				"dee",
				"random"
			),
			array(),
			array(
				1,
				2,
				3,
				4,
				5
			)
		);
		$seps = array(
			";",
			".",
			"/",
			" ",
			"DUDE"
		);
		$adds = array(
			array(
				"a",
				"b",
				"c",
				"dee",
				"random"
			),
			array(
				"dude"
			),
			array(),
			array(
				null
			),
			array(
				"",
				"",
				""
			),
			array(
				1,
				2,
				3,
				""
			),
			array(
				1,
				2,
				3,
				4,
				5
			)
		);
		$datum = array();
		foreach (array(
			false,
			true
		) as $list_is_array) {
			foreach (array(
				false,
				true
			) as $add_is_array) {
				foreach ($lists as $list) {
					foreach ($adds as $add) {
						foreach ($seps as $sep) {
							$add_cleaned = count($add) === 0 ? array() : ($add_is_array ? arr::clean($add, null) : explode($sep, implode($sep, $add)));
							$expected = $list_is_array ? array_merge($list, $add_cleaned) : implode($sep, array_merge($list, $add_cleaned));
							
							$datum[] = array(
								$list_is_array ? $list : implode($sep, $list),
								$add_is_array ? $add : (count($add) === 0 ? null : implode($sep, $add)),
								$sep,
								$expected
							);
						}
					}
				}
			}
		}
		return $datum;
	}
	
	/**
	 * @data_provider append_data_provider
	 */
	function test_append($list, $add, $sep, $expected) {
		$actual = lists::append($list, $add, $sep);
		$this->assert_equal(type($actual), type($expected), "Type of list does not match return type");
		$this->assert_equal($actual, $expected, map("lists::append({list}, {add}, \"$sep\")", array(
			"list" => _dump($list),
			"add" => _dump($add)
		)), false);
	}
	
	/**
	 * @data_provider append_data_provider
	 */
	function test_append_unique($list, $add, $sep, $expected) {
		if (is_array($expected)) {
			$expected = array_unique($expected);
		} else {
			$expected = implode($sep, array_unique(explode($sep, $expected)));
		}
		$actual = lists::append_unique($list, $add, $sep);
		$this->assert_equal(type($actual), type($expected));
		$this->assert_equal($actual, $expected);
	}
	function test_contains() {
		$llist = null;
		$item = null;
		$sep = ';';
		$this->assert_true(lists::contains("a;b;c;d;ee;ff", "a"));
		$this->assert_true(lists::contains("a;b;c;d;ee;ff", "b"));
		$this->assert_true(lists::contains("a;b;c;d;ee;ff", "c"));
		$this->assert_true(lists::contains("a;b;c;d;ee;ff", "d"));
		$this->assert_true(lists::contains("a;b;c;d;ee;ff", "ee"));
		$this->assert_true(lists::contains("a;b;c;d;ee;ff", "ff"));
	}
	function test_pop() {
		$llist = null;
		$sep = ';';
		lists::pop($llist, $sep);
	}
	function test_prepend() {
		$llist = null;
		$item = null;
		$sep = ';';
		lists::prepend($llist, $item, $sep);
	}
	function test_remove() {
		$llist = null;
		$item = null;
		$sep = ';';
		lists::remove($llist, $item, $sep);
	}
	function test_unique() {
		$llist = "X;a;A;b;b;c;c;D;F;a;X";
		$sep = ";";
		$this->assert(lists::unique($llist, $sep) === "X;a;A;b;c;D;F");
		
		echo basename(__FILE__) . ": success\n";
	}
}
