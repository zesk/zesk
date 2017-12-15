<?php
namespace zesk;

class Adapter_TestFramework extends Test implements Interface_Testable {
	function assertTrue($condition, $message = null) {
		$this->assert_true($condition, $message);
	}
	function assertFalse($condition, $message = null) {
		$this->assert_false($condition, $message);
	}
}