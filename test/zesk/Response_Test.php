<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Response_Test extends UnitTest
{
	/**
	 *
	 */
	public function test_body_attributes(): void
	{
		$request = new Request($this->application);
		$response = $this->application->responseFactory($request);

		$add = null;

		$response->html()->addBodyAttributes(['goo' => 'bar']);
		$response->html()->addBodyAttributes([
			'goo' => 'actual',
		]);
		$response->html()->addBodyAttributes([
			'poo' => 'bar',
		]);
		$response->html()->addBodyAttributes(['poo' => 'actual']);
		$response->html()->addBodyAttributes([
			'dee' => 'foofla',
		]);
		$response->html()->addBodyAttributes(['dee'=> 'actual']);
		$response->html()->addBodyAttributes(['loo' => 'actual']);

		$attrs = $response->html()->bodyAttributes();

		$compare_result = [
			'goo' => 'actual',
			'poo' => 'actual',
			'dee' => 'actual',
			'loo' => 'actual',
		];

		$this->assertEquals($attrs, $compare_result);
	}

	/**
	 *
	 */
	public function test_scripts(): void
	{
		$request = new Request($this->application);
		$response = $this->application->responseFactory($request);

		$type = 'text/javascript';
		$script = 'alert(\'Hello, world!\');';
		$response->html()->inlineJavaScript($script, [
			'browser' => 'ie',
		]);

		$content = $this->application->themes->theme('Response/HTML/scripts', [
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

		$this->assertStringContainsString('<!--[if IE]><script>alert(\'Hello, world!\');</script><![endif]-->', $content);
	}
}
