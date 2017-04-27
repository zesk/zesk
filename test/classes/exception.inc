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
class test_exception extends Test_Unit {

	function test_exception_directory_not_found() {
		$message = null;
		$code = null;
		$x = new Exception_Directory_NotFound(ZESK_ROOT);

		$x->getMessage();

		$x->getCode();

		$x->getFile();

		$x->getLine();

		$x->getTrace();

		$x->getTraceAsString();

		$x->__toString();
	}

	function test_exception_directory_create() {
		$testx = new Exception_Directory_Create(ZESK_ROOT);

		$testx->getMessage();

		$testx->getCode();

		$testx->getFile();

		$testx->getLine();

		$testx->getTrace();

		$testx->getPrevious();

		$testx->getTraceAsString();

		$testx->__toString();
	}
}
