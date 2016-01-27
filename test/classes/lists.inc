<?php
class test_lists extends Test_Unit {

	function test_append() {
		$llist = null;
		$item = null;
		$sep = ';';
		lists::append($llist, $item, $sep);
	}

	function test_append_unique() {
		$llist = null;
		$item = null;
		$sep = ';';
		lists::append_unique($llist, $item, $sep);
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
