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
class Response_Test extends UnitTest {
	/**
	 *
	 */
	public function test_body_attributes(): void {
		$request = new Request($this->application);
		$response = $this->application->responseFactory($request);

		$add = null;

		$response->html()->body_attributes('goo', 'bar');
		$response->html()->body_attributes([
			'goo' => 'actual',
		]);
		$response->html()->body_attributes([
			'poo' => 'bar',
		]);
		$response->html()->body_attributes('poo', 'actual');
		$response->html()->body_attributes([
			'dee' => 'foofla',
		]);
		$response->html()->body_attributes('dee', 'actual');
		$response->html()->body_attributes('loo', 'actual');

		$attrs = $response->html()->body_attributes();

		$compare_result = [
			'goo' => 'actual',
			'poo' => 'actual',
			'dee' => 'actual',
			'loo' => 'actual',
		];

		$this->assert_arrays_equal($attrs, $compare_result);
	}

	/**
	 *
	 */
	public function test_scripts(): void {
		$request = new Request($this->application);
		$response = $this->application->responseFactory($request);

		$type = 'text/javascript';
		$script = 'alert(\'Hello, world!\');';
		$response->html()->javascript_inline($script, [
			'browser' => 'ie',
		]);

		$content = $this->application->theme('response/html/scripts', [
			'response' => $response,
			'jquery_ready' => [],
		]);
		$scripts = $response->html()->scripts();

		$this->assertTrue(is_array($scripts), 'Scripts is array');

		$this->assertStringContainsString($script, $content);
		$this->assertStringContainsString('<!--', $content);
		$this->assertStringContainsString('[if IE]', $content);
		$this->assertStringContainsString('<![endif]-->', $content);
		$this->assertStringContainsString('<![endif]-->', $content);

		$this->assertStringContainsString('<!--[if IE]><script type="text/javascript">alert(\'Hello, world!\');</script><![endif]-->', $content);
	}
}
