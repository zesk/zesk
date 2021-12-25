<?php declare(strict_types=1);

/**
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
	public function test_main(): void {
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

		$options = [
			"echo_log" => true,
		];
		$testx = new Net_SMTP_Client($this->application, $url, $options);

		echo "Hello";

		$testx->connect();

		$headers = null;
		$body = null;
		$testx->send($from, $to, $headers, $body);
	}
}
