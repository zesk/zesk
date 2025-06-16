<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk\Net\HTTP;

use zesk\Exception\ConnectionFailed;
use zesk\Exception\ProtocolException;
use zesk\Net\HTTP\Client;
use zesk\UnitTest;

/**
 *
 * @author kent
 * @coversDefaultClass  \zesk\Net\HTTP\Client
 */
class ClienTestCase extends UnitTest
{
	public const TEST_URL = 'https://build.zesk.com/';

	/**
	 * @return void
	 * @covers ::userAgent
	 */
	public function test_all(): void
	{
		$url = self::TEST_URL;

		$x = new Client($this->application, $url);

		$this->assertNotEquals('', $x->userAgent());
	}

	/**
	 * @return void
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @covers ::simpleGet
	 */
	public function do_not_test_simpleGet(): void
	{
		$url = 'https://127.0.0.1/';
		Client::simpleGet($url);
	}

	/**
	 * @return void
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @covers ::simpleGet
	 */
	public function no_test_main(): void
	{
		$url = self::TEST_URL;

		$result = Client::simpleGet($url);
		$this->assertIsString($result);
		$this->assertTrue(str_contains($result, 'Market Acumen'), $result);
	}

	/**
	 * @return void
	 * @covers ::urlFetchContentLength
	 */
	public function test_urlFetchContentLength(): void
	{
		$url = self::TEST_URL;
		$n = Client::urlFetchContentLength($this->application, $url);
		$this->assertGreaterThan(0, $n);
	}

	/**
	 * @return void
	 * @covers ::urlFetchHeaders
	 */
	public function test_urlFetchHeaders(): void
	{
		$url = self::TEST_URL;
		$headers = Client::urlFetchHeaders($this->application, $url);
		$this->assertStringStartsWith('text/html', $headers['Content-Type']);
	}

	/**
	 * @return void
	 * @covers ::defaultUserAgent
	 */
	public function test_default_userAgent(): void
	{
		$client = new Client($this->application);
		$this->assertStringStartsWith('zesk', $client->defaultUserAgent());
	}
}
