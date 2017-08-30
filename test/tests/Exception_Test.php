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
class Exception_Test extends Exception_TestCase {
	function test_exception_directory_not_found() {
		$x = new Exception_Directory_NotFound(ZESK_ROOT);
		
		$this->exception_test($x);
	}
	function test_exception_directory_create() {
		$x = new Exception_Directory_Create(ZESK_ROOT);
		
		$this->exception_test($x);
	}
}
