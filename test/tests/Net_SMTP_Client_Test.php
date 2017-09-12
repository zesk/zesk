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
			throw new Exception_Configuration("No URL specified for " . __CLASS__);
		}
		$from = $this->option("from");
		if (!$from) {
			throw new Exception_Configuration("No from specified for " . __CLASS__);
		}
		$to = $this->option("to");
		if (!$to) {
			throw new Exception_Configuration("No to specified for " . __CLASS__);
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
