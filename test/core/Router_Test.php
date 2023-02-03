<?php declare(strict_types=1);
/**
 * @test_sandbox true
 */
namespace zesk;

class Router_Test extends UnitTest {
	public function test_Router_sleep(): void {
		$router = new Router($this->application);
		$router->addRoute('foo', ['content' => 'bar']);
		$routes = $router->routes();
		$this->assertCount(1, $routes);

		$data = serialize($router);

		$result = PHP::unserialize($data);

		$this->assertInstanceOf(Router::class, $result);
		$routes = $result->routes();
		$this->assertCount(1, $routes);
	}

	public function test_Router(): void {
		$this->application->setDocumentRootPrefix('');

		$testx = new Router($this->application);

		$hash = md5(microtime());
		$template = $this->test_sandbox('Router-Test.tpl');
		file_put_contents($template, "<?php\necho \"$hash\";");

		$this->application->addThemePath($this->test_sandbox());

		$url_pattern = 'foo';
		$testx->addRoute($url_pattern, [
			'theme' => 'Router-Test',
		]);

		$request = Request::factory($this->application, [
			'url' => 'http://test/foo',
		]);
		$route = $testx->matchRequest($request);
		$this->assertInstanceOf(Route::class, $route);
		$app = $this->application;

		$app->router = $testx;
		$request = Request::factory($this->application, 'http://www.example.com/foo');
		$response = $app->main($request);

		// Avoids doing header() in test code
		$response->setOption(Response::OPTION_SKIP_HEADERS, true);
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

		$request = Request::factory($this->application, [
			'url' => 'http://test/',
		]);
		$this->expectException(Exception_NotFound::class);
		$this->assertNull($testx->matchRequest($request));
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
		$this->application->cacheItemPool()->commit();
		$router->cached($mtime);
	}
}
