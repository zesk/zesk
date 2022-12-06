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

		$this->application->addThemePath($this->test_sandbox());

		$url_pattern = 'foo';
		$testx->addRoute($url_pattern, [
			'theme' => 'Router-Test',
		]);

		$request = new Request($this->application, [
			'url' => 'http://test/foo',
		]);
		$route = $testx->match($request);
		$this->assertInstanceOf(Route::class, $route);
		$app = $this->application;

		$app->router = $testx;
		$request = Request::factory($this->application, 'http://www.example.com/foo');
		$response = $app->main($request);

		// Avoids doing header() in test code
		$response->setOption('skip_response_headers', true);
		$content = $response->render();

		$this->assertStringContainsString($hash, $content);
	}

	public function test_Router_nf(): void {
		$testx = new Router($this->application);

		$hash = md5(microtime());
		$template = $this->test_sandbox('Router-Test.tpl');
		file_put_contents($template, "<?php\necho \"$hash\";");

		$this->application->addThemePath($this->test_sandbox());

		$url_pattern = 'foo';
		$testx->addRoute($url_pattern, [
			'theme' => 'Router-Test',
		]);

		$request = new Request($this->application, [
			'url' => 'http://test/',
		]);
		$this->expectException(Exception_NotFound::class);
		$this->assertNull($testx->match($request));
	}

	public function test_not_cached(): void {
		$mtime = 'null';
		$router = new Router($this->application);
		$this->expectException(Exception_NotFound::class);
		$router->cached($mtime);
	}

	public function test_yes_cached(): void {
		$mtime = 'yes';
		$router = new Router($this->application);
		$router->cache($mtime);
		$this->application->cache->commit();
		$router->cached($mtime);
	}
}
