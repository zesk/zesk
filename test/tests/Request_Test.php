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
	function test_instance() {
		$request = $this->application->request();
		$this->assert_instanceof($request, __NAMESPACE__ . "\\" . "Request");
	}
	function test_put_request() {
		//Request::put_request(); // Write to stdin to solve this one TODO
	}
	function test_Request() {
		$settings = array(
			"url" => "https://ex.to/"
		);
		$testx = new Request($settings);
		
		$testx->is_post();
		
		$name = null;
		$value = null;
		$testx->set($name, $value);
		
		$default = null;
		$testx->get("Hello", $default);
		
		$testx->path();
	}
	
	/**
	 * @expected_exception zesk\Exception_File_Permission
	 */
	function test__file_migrate1() {
		$source = $this->test_sandbox(__FUNCTION__ . '.txt');
		file_put_contents($source, $source);
		$upload_array = array(
			'tmp_name' => $source
		);
		$this->assert(file_exists($source));
		$dest_path = $this->test_sandbox(__FUNCTION__ . '-dest.txt');
		$file_mode = false;
		$hash_image = false;
		Request::file_migrate($upload_array, $dest_path, $file_mode, $hash_image);
	}
}
