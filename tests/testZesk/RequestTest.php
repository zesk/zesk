<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\ClassNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\HTTP;
use zesk\Request;
use zesk\Request\File as FileRequest;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\Request
 * @uses   Request
 */
class RequestTest extends TestCase
{
	/**
	 * @return void
	 * @covers ::ip
	 * @throws SemanticsException
	 */
	public function test_remote(): void
	{
		$request = new Request($this->application);
		$default = '0.0.0.0';
		$this->assertEquals($default, $request->ip());
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 * @throws FileNotFound
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @covers ::initializeFromSettings
	 * @covers ::isSecure
	 * @covers ::isPost
	 * @covers ::contentType
	 * @covers ::path
	 * @covers ::parseAccept
	 * @covers ::data
	 * @covers ::headers
	 * @covers ::header
	 */
	public function test_Request(): void
	{
		$settings = [
			'url' => 'https://ex.to/hello',
			'headers' => ['Content-Type' => 'text/plain; yothis is ignored'],
		];
		$request = new Request($this->application);
		$request->initializeFromSettings($settings);

		$this->assertTrue($request->isSecure());
		$this->assertFalse($request->isPost());
		$this->assertEquals('text/plain', $request->contentType());

		$this->assertEquals([
			'*/*' => [
				'q' => 1,
				'pattern' => '#[^/]+/[^/]+#',
			],
		], $request->parseAccept());

		$this->assertEquals('/hello', $request->path());
		$this->assertEquals([], $request->data());

		$this->assertEquals(['Content-Type' => 'text/plain; yothis is ignored'], $request->headers());
		$this->assertEquals('text/plain; yothis is ignored', $request->header(HTTP::REQUEST_CONTENT_TYPE));
	}

	/**
	 * @covers File::migrate
	 */
	public function test__file_migrate1(): void
	{
		$this->expectException(FilePermission::class);
		$source = $this->test_sandbox(__FUNCTION__ . '.txt');
		file_put_contents($source, $source);
		$upload_array = [
			'tmp_name' => $source,
		];
		$this->assertTrue(file_exists($source));
		$dest_path = $this->test_sandbox(__FUNCTION__ . '-dest.txt');
		$options = [];
		$filename = FileRequest::instance($upload_array)->migrate($this->application, $dest_path, $options);
		$this->assertNull($filename);
	}

	/**
	 * Should prefer HTML in this case.
	 * @covers ::preferJSON
	 */
	public function test_msie_broken(): void
	{
		$settings = [
			'url' => 'https://autotest.zesk.com/',
			'ip' => '127.0.0.1',
			'headers' => [
				'Accept' => 'text/html, application/xhtml+xml, */*',
				'Accept-Language' => 'en-US',
				'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
				'Accept-Encoding' => 'gzip, deflate',
				'Host' => 'dc.timebanks.org',
				'Connection' => 'Keep-Alive',
				'Cache-Control' => 'no-cache',
				'Cookie' => 'CWCOOKIE=a63dde59ac85d83734e2c137fb747445; AWSALB=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2; AWSALBCORS=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2',
			],
		];

		$request = Request::factory($this->application, $settings);

		$this->assertFalse($request->preferJSON(), 'Request should NOT prefer JSON');
	}

	/**
	 * @return void
	 * @throws FileNotFound
	 * @throws ClassNotFound
	 * @covers ::preferJSON
	 */
	public function test_chrome_works_fine(): void
	{
		$settings = [
			'url' => 'https://autotest.zesk.com/',
			'ip' => '127.0.0.1',
			'headers' => [
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
				'Accept-Language' => 'en-US,en;q=0.9',
				'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
				'Accept-Encoding' => 'gzip, deflate, br',
				'Host' => 'dc.timebanks.org',
				'Connection' => 'Keep-Alive',
				'Cache-Control' => 'max-age=0',
				'Cookie' => 'CWCOOKIE=a63dde59ac85d83734e2c137fb747445; AWSALB=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2; AWSALBCORS=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2',
			],
		];

		$request = Request::factory($this->application, $settings);

		$this->assertFalse($request->preferJSON(), 'Request should NOT prefer JSON');
	}
}
