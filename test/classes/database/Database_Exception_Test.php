<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/Database_Exception.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Exception_Test extends Test_Unit {
	public function test_basics() {
		$db = null;
		$sql = null;
		$errno = 0;
		$message = null;
		$testx = new Database_Exception($db, $sql, $errno, $message);
		
		$testx->__toString();
		
		$testx->getMessage();
		
		$testx->getCode();
		
		$testx->getFile();
		
		$testx->getLine();
		
		$testx->getTrace();
		
		$testx->getPrevious();
		
		$testx->getTraceAsString();
	}
}

