<?php
class test_doccomment extends Test_Unit {

	function test_extract() {
		$content = file_get_contents(ZESK_ROOT . 'classes/arr.inc');
		$comments = doccomment::extract($content);
		dump($comments);
	}

	function test_clean() {
		$string = null;
		doccomment::clean($string);
	}

	function test_parse() {
		$string = null;
		doccomment::parse($string);
	}

	function test_unparse() {
		$items = array("param" => array("line1", "line2"), "desc" => "Description");
		$this->assert_equal(doccomment::unparse($items), "/**\n * @param line1\n *        line2\n * @desc  Description\n */");
	}
}
