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
class Request_Test extends Test_Unit {
	public function test_instance() {
		$request = $this->application->request();
		$this->assert_null($request, __NAMESPACE__ . "\\" . "Request");
		$request = new Request($this->application);
		$this->assert_instanceof($request, __NAMESPACE__ . "\\" . "Request");
	}

	public function test_put_request() {
		//Request::put_request(); // Write to stdin to solve this one TODO
	}

	public function test_Request() {
		$settings = array(
			"url" => "https://ex.to/",
		);
		$testx = new Request($this->application, $settings);

		$testx->is_post();

		$name = null;
		$value = null;
		$testx->set($name, $value);

		$default = null;
		$testx->get("Hello", $default);

		$testx->path();
	}

	/**
	 * @expectedException zesk\Exception_File_Permission
	 */
	public function test__file_migrate1() {
		$source = $this->test_sandbox(__FUNCTION__ . '.txt');
		file_put_contents($source, $source);
		$upload_array = array(
			'tmp_name' => $source,
		);
		$this->assert(file_exists($source));
		$dest_path = $this->test_sandbox(__FUNCTION__ . '-dest.txt');
		$options = array();
		$filename = Request\File::instance($upload_array)->migrate($this->application, $dest_path, $options);
	}

	/**
	 * Should prefer HTML in this case.
	 */
	public function test_msie_broken() {
		$settings = [
			'url' => 'https://autotest.zesk.com/',
			'ip' => '127.0.0.1',
			'headers' => [
				"Accept" => "text/html, application/xhtml+xml, */*",
				"Accept-Language" => "en-US",
				"User-Agent" => "Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko",
				"Accept-Encoding" => "gzip, deflate",
				"Host" => "dc.timebanks.org",
				"Connection" => "Keep-Alive",
				"Cache-Control" => "no-cache",
				"Cookie" => "CWCOOKIE=a63dde59ac85d83734e2c137fb747445; AWSALB=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2; AWSALBCORS=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2",
			],
		];

		$request = Request::factory($this->application, $settings);

		$this->assertFalse($request->prefer_json(), "Request should NOT prefer JSON");
	}

	public function test_chrome_works_fine() {
		$settings = [
			'url' => 'https://autotest.zesk.com/',
			'ip' => '127.0.0.1',
			'headers' => [
				"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
				"Accept-Language" => "en-US,en;q=0.9",
				"User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36",
				"Accept-Encoding" => "gzip, deflate, br",
				"Host" => "dc.timebanks.org",
				"Connection" => "Keep-Alive",
				"Cache-Control" => "max-age=0",
				"Cookie" => "CWCOOKIE=a63dde59ac85d83734e2c137fb747445; AWSALB=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2; AWSALBCORS=1cKvKVLPxVDY8cef7JQMjqou7t1ABG0ctym4vg0EWGENnsXnPNV1oRnA5cFVNOktenQFLojlRt+usB7N+0V8RTsiAkr+WUIHvzqMjSMyCEwnWjqqktx8goRxrxE2",
			],
		];

		$request = Request::factory($this->application, $settings);

		$this->assertFalse($request->prefer_json(), "Request should NOT prefer JSON");
	}
}
