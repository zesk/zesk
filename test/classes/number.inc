<?php
class test_number extends Test_Unit {

	function test_format_bytes() {
		$tests = array(
			0 => "0 bytes",
			1 => "1 byte",
			2 => "2 bytes",
			1024 => "1 KB",
			1024 * 1024 => "1 MB",
			1536133 => "1.5 MB"
		);

		foreach ($tests as $n => $result) {
			$this->assert_equal(Number::format_bytes($n), $result, "Number::format_bytes($n)");
		}
	}
}

