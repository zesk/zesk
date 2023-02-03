<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_SMTP_Client_Test extends UnitTest {
	public function test_main(): void {
		$url = $this->option('url');
		if (!$url) {
			$this->markTestSkipped('No URL specified for ' . __CLASS__);
		}
		$from = $this->option('from');
		if (!$from) {
			$this->markTestSkipped('No from specified for ' . __CLASS__);
		}
		$to = $this->option('to');
		if (!$to) {
			$this->markTestSkipped('No to specified for ' . __CLASS__);
		}

		$options = [
			'echo_log' => true,
		];
		$client = new Net_SMTP_Client($this->application, $url, $options);

		$client->connect();

		$headers = null;
		$body = null;
		$client->send($from, $to, $headers, $body);
	}
}
