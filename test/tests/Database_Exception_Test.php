<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/Database_Exception.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Exception_Test extends Exception_TestCase {
	public function test_basics() {
		$testx = new Database_Exception();
		
		$this->exception_test($testx);
	}
}

