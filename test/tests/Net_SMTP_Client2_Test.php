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
class Net_SMTP_Client_Test extends Test_Unit {
	function test_Net_SMTP_Client() {
		$url = "smtp://mail.conversionruler.com";
		$options = array(
			"timeout" => 30
		);
		$x = new Net_SMTP_Client($url, $options);

		$x->connect();

		$from = "no-reply@zesk.com";
		$to = "test-mailbox-0@zesk.com";
		$headers = array(
			"From: $from",
			"To: $to",
			"Subject: Test message " . posix_getpid()
		);
		$body = "Sent on " . gmdate("Y-m-d H:i:s\n");
		$result = $x->go($from, $to, $headers, $body);

		$this->assert($result === true);
	}
}
