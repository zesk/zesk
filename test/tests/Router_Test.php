<?php declare(strict_types=1);
/**
 * @test_sandbox true
 */
namespace zesk;

class Router_Test extends UnitTest {
	public function test_Router(): void {
		$testx = new Router($this->application);

		$hash = md5(microtime());
		$template = $this->test_sandbox('Router-Test.tpl');
		file_put_contents($template, "<?php\necho \"$hash\";");

		$this->application->theme_path($this->test_sandbox());

		$url_pattern = 'foo';
		$testx->add_route($url_pattern, [
			'theme' => 'Router-Test',
		]);

		$request = new Request($this->application, [
			'url' => 'http://test/',
		]);
		$this->assertNull($testx->match($request));

		$app = $this->application;

		$app->router = $testx;
		$request = Request::factory($this->application, 'http://www.example.com/foo');
		$response = $app->main($request);

		// Avoids doing header() in test code
		$response->setOption('skip_response_headers', true);
		$content = $response->render();

		$this->assertStringContainsString($hash, $content);
	}

	public function test_cached(): void {
		$mtime = null;
		$router = new Router($this->application);
		$result = $router->cached($mtime);
		$this->assert_null($result);
	}
}
