<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_SMTP_Client2_Test extends UnitTest {
	private string $url;

	public function test_outgoing_requirements(): void {
		$this->url = $this->option('url', '');
		if (!URL::valid($this->url)) {
			$this->markTestSkipped(get_class($this) . '::url not valid (' . $this->url . ')');
		}
		$parts = URL::parse($this->url);
		$this->assertArrayHasKeys(['scheme', 'user', 'pass', 'host', 'port'], $parts);
	}

	/**
	 * @return void
	 * @throws Exception_Connect
	 * @throws Exception_Syntax
	 * @depends test_outgoing_requirements
	 */
	public function test_Net_SMTP_Client(): void {
		$options = [
			'timeout' => 30,
		];
		$x = new Net_SMTP_Client($this->application, $this->url, $options);

		$x->connect();

		$from = 'no-reply@zesk.com';
		$to = 'zesk-test-0@zesk.com';
		$headers = [
			"From: $from",
			"To: $to",
			'Subject: Test message ' . posix_getpid(),
		];
		$body = 'Sent on ' . gmdate("Y-m-d H:i:s\n");
		$result = $x->send($from, $to, $headers, $body);

		$this->assert($result === true);
	}
}
