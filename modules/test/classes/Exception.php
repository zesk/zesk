<?php
namespace zesk;

class Test_Exception extends Test_Unit {
	function simple_exceptions() {
		return array(
			new \Exception("message", 123, new \Exception("previous", 345)),
			new Exception("Hello {thing}", array(
				"thing" => "world"
			))
		);
	}
	/**
	 * @data_provider simple_exceptions
	 * @param \Exception $e
	 */
	function exception_test(\Exception $e) {
		$this->assert_is_string($e->getMessage());
		
		$this->assert_is_integer($e->getCode());
		
		$this->assert_is_string($e->getFile());
		
		$e->getLine();
		
		$e->getTrace();
		
		$e->getPrevious();
		
		$e->getTraceAsString();
		
		$e->__toString();
	}
}