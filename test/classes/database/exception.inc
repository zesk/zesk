<?php
class Test_Database_Exception extends Test_Unit {

	function test_main() {
		$message = null;
		$code = null;
		$stuff = null;
		$others = null;
		$x = new Database_Exception($message, $code, $stuff, $others);

		$x->getMessage();

		$x->getCode();

		$x->getFile();

		$x->getLine();

		$x->getTrace();

		$x->getTraceAsString();

		$x->__toString();

		echo basename(__FILE__) . ": success\n";
	}
}
