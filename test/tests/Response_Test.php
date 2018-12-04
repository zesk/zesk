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
class Response_Test extends Test_Unit {
	/**
	 *
	 */
	public function test_body_attributes() {
		$request = new Request($this->application);
		$response = $this->application->response_factory($request);

		$add = null;

		$response->html()->body_attributes("goo", "bar");
		$response->html()->body_attributes(array(
			"goo" => "actual",
		));
		$response->html()->body_attributes(array(
			"poo" => "bar",
		));
		$response->html()->body_attributes("poo", "actual");
		$response->html()->body_attributes(array(
			"dee" => "foofla",
		));
		$response->html()->body_attributes("dee", "actual");
		$response->html()->body_attributes("loo", "actual");

		$attrs = $response->html()->body_attributes();

		$compare_result = array(
			"goo" => "actual",
			"poo" => "actual",
			"dee" => "actual",
			"loo" => "actual",
		);

		$this->assert_arrays_equal($attrs, $compare_result);
	}

	/**
	 *
	 */
	public function test_scripts() {
		$request = new Request($this->application);
		$response = $this->application->response_factory($request);

		$type = "text/javascript";
		$script = "alert('Hello, world!');";
		$response->html()->javascript_inline($script, array(
			'browser' => 'ie',
		));

		$content = $this->application->theme("response/html/scripts", array(
			"response" => $response,
			'jquery_ready' => array(),
		));
		$scripts = $response->html()->scripts();

		$this->assertTrue(is_array($scripts), "Scripts is array");

		$this->assertContains($script, $content);
		$this->assertContains("<!--", $content);
		$this->assertContains("[if IE]", $content);
		$this->assertContains("<![endif]-->", $content);
		$this->assertContains("<![endif]-->", $content);

		$this->assertContains('<!--[if IE]><script type="text/javascript">alert(\'Hello, world!\');</script><![endif]-->', $content);
	}
}
