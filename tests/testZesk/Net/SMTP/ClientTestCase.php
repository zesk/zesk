<?php
declare(strict_types=1);
/**
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Net\SMTP;

use testZesk\TestCase;
use zesk\Exception\ConnectionFailed;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Net\SMTP\Client;
use zesk\URL;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\Net\SMTP\Client
 */
class ClientTestCase extends TestCase
{
	private string $url;

	/**
	 * @return void
	 * @throws SyntaxException
	 * @throws SemanticsException
	 * @coversNothing
	 */
	public function testRequirements(): void
	{
		$this->url = $this->app()->environment()->getString('SMTP_URL');
		if (!URL::valid($this->url)) {
			$this->markTestSkipped(get_class($this) . '::url not valid (' . $this->url . ')');
		}
		$parts = URL::parse($this->url);
		$this->assertArrayHasKeys([
			'scheme',
			'user',
			'pass',
			'host',
			'port',
		], $parts);
	}

	/**
	 * @return void
	 * @depends testRequirements
	 * @return void
	 * @throws ConnectionFailed
	 * @throws SyntaxException
	 * @covers ::send
	 */
	public function testClient(): void
	{
		$options = [
			'timeout' => 30,
		];
		$x = new Client($this->application, $this->url, $options);

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

		$this->assertTrue($result);
	}
}
