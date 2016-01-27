<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/net/ftp/Net_FTP_Client_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Net_FTP_Client_Test extends Test_Unit {
	function test_basics() {
		$url = 'ftp://user:pass@localhost';
		$directory = null;
		$log_file = false;
		$testx = new Net_FTP_Client($url);
		
		$isOn = true;
		$testx->passive($isOn);
		
		$this->assert(!$testx->is_connected());
	}
}
