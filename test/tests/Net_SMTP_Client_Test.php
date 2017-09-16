<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/net/smtp/Net_SMTP_Client_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_SMTP_Client_Test extends Test_Unit {
	function test_main() {
		$url = $this->option("url");
		if (!$url) {
			$this->markTestIncomplete("No URL specified for " . __CLASS__);
		}
		$from = $this->option("from");
		if (!$from) {
			$this->markTestIncomplete("No from specified for " . __CLASS__);
		}
		$to = $this->option("to");
		if (!$to) {
			$this->markTestIncomplete("No to specified for " . __CLASS__);
		}
		
		$options = array(
			"echo_log" => true
		);
		$testx = new Net_SMTP_Client($this->application, $url, $options);
		
		echo "Hello";
		
		$testx->connect();
		
		$headers = null;
		$body = null;
		$testx->send($from, $to, $headers, $body);
	}
}
