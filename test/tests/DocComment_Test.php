<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class test_doccomment extends Test_Unit {
	function test_extract() {
		$content = file_get_contents(ZESK_ROOT . 'classes/arr.inc');
		$comments = DocComment::extract($content);
		dump($comments);
	}
	function test_clean() {
		$string = null;
		DocComment::clean($string);
	}
	function test_parse() {
		$string = null;
		DocComment::parse($string);
	}
	function test_unparse() {
		$items = array(
			"param" => array(
				"line1",
				"line2"
			),
			"desc" => "Description"
		);
		$this->assert_equal(DocComment::unparse($items), "/**\n * @param line1\n *        line2\n * @desc  Description\n */");
	}
}
