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

		dump($attrs, $compare_result);
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

		$scripts = $response->html()->scripts();

		$this->assert(strpos($scripts, $script) !== false);
		$this->assert(strpos($scripts, "<!--") !== false);
		$this->assert(strpos($scripts, "[if IE]") !== false);
		$this->assert(strpos($scripts, "<![endif]-->") !== false);

		$this->assert_equal($scripts, '<!--[if IE]><script type="text/javascript">alert(\'Hello, world!\');</script><![endif]-->');
	}
}
