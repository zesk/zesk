<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Exception\SystemException;
use zesk\Request;
use zesk\Response;
use zesk\Router;
use zesk\PHP;

class RouterTest extends ApplicationTestBase
{
	/**
	 * @throws ConfigurationException
	 * @throws SystemException
	 * @throws SemanticsException
	 * @throws NotFoundException
	 */
	public function test_routerReverse(): void
	{
		$this->testApplication->configure();

		$router = $this->testApplication->router();

		$route = $router->getRoute('home');
		$this->assertEquals('home', $route);

		$route = $router->getRoute('home2');
		$this->assertEquals('homeNOMAP', $route);

		$route = $router->getRoute('dumpRoute');
		$this->assertEquals('dump/route', $route);

		$route = $router->getRoute('cache');
		$this->assertEquals('cache', $route);
	}

	/**
	 * @throws ClassNotFound
	 * @throws SyntaxException
	 */
	public function test_Router_sleep(): void
	{
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

	/**
	 * @throws NotFoundException
	 * @throws ClassNotFound
	 * @throws DirectoryNotFound
	 * @throws SemanticsException
	 * @throws FileNotFound
	 */
	public function test_Router(): void
	{
		$this->application->setDocumentRootPrefix('');

		$testObject = new Router($this->application);

		$hash = md5(microtime());
		$template = $this->test_sandbox('Router-Test.tpl');
		file_put_contents($template, "<?php\necho \"$hash\";");

		$this->application->addThemePath($this->test_sandbox());

		$url_pattern = 'foo';
		$testObject->addRoute($url_pattern, [
			'theme' => 'Router-Test',
		]);

		$request = Request::factory($this->application, [
			'url' => 'http://test/foo',
		]);
		$route = $testObject->matchRequest($request);
		$this->assertNotNull($route);

		$app = $this->application;

		$app->router = $testObject;
		$request = Request::factory($this->application, 'https://www.example.com/foo');
		$response = $app->main($request);

		// Avoids doing header() in test code
		$response->setOption(Response::OPTION_SKIP_HEADERS, true);
		$content = $response->render();

		$this->assertStringContainsString($hash, $content);
	}

	/**
	 * @throws ClassNotFound
	 * @throws DirectoryNotFound
	 * @throws FileNotFound
	 */
	public function test_Router_nf(): void
	{
		$testObject = new Router($this->application);

		$hash = md5(microtime());
		$template = $this->test_sandbox('Router-Test.tpl');
		file_put_contents($template, "<?php\necho \"$hash\";");

		$this->application->addThemePath($this->test_sandbox());

		$url_pattern = 'foo';
		$testObject->addRoute($url_pattern, [
			'theme' => 'Router-Test',
		]);

		$request = Request::factory($this->application, [
			'url' => 'http://test/',
		]);
		$this->expectException(NotFoundException::class);
		$this->assertNull($testObject->matchRequest($request));
	}

	public function test_not_cached(): void
	{
		$mtime = 'null';
		$router = new Router($this->application);
		$this->expectException(NotFoundException::class);
		$router->cached($mtime);
	}

	/**
	 * @throws NotFoundException
	 * @covers ::cache ::cached
	 */
	public function test_yes_cached(): void
	{
		$mtime = 'yes';
		$router = new Router($this->application);
		$router->cache($mtime);
		$this->application->cacheItemPool()->commit();
		$router->cached($mtime);
	}
}
