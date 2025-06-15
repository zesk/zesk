<?php
declare(strict_types=1);

namespace testZesk;

use zesk\File;
use zesk\Request;
use zesk\Response;
use zesk\Route\Controller as ControllerRoute;
use zesk\StringTools;

/**
 * @coversDefaultClass \zesk\Controller
 */
class ControllerTest extends TestCase
{
	/**
	 * @covers ::execute
	 */
	public function test_Controller(): void
	{
		$app = $this->application;

		$app->addThemePath($this->test_sandbox());
		$themeContent = "--Test--\n";
		$themeContent .= '<' . "?php\n";
		$themeContent .= "echo \"\$content\n\";\n";
		$themeContent .= "echo gettype(\$content) . \"\n\";\n";
		$themeContent .= "?>\nEOF";

		File::put($this->test_sandbox('test-theme.tpl'), $themeContent);

		$app->setOption('cache_router', false);
		$router = $app->router();
		$router->clear();

		$router->addRoute('({first}(/{second}))', [
			'controller' => TestController::class,
			'arguments'  => [
				0,
				1,
			],
			'action'     => 'index',
			'default'    => true,
			'weight'     => 1,
		]);
		$testRouteStart = 'array'; /* No slashes allowed - indexes 1,2 */
		$router->addRoute($testRouteStart . '(/{what}(/{verb}))', [
			'controller' => TestController::class,
			'arguments'  => [
				1,
				2,
				'{request}',
			],
			'action'     => 'array',
			'weight'     => -1,
		]);

		$expected = ['init'];
		foreach ([
			'',
			"$testRouteStart",
			"$testRouteStart/1",
			"$testRouteStart/Love",
			"$testRouteStart/One",
			"$testRouteStart/Heart/...lets-get",
		] as $suffix) {
			$request = Request::factory($app, 'http://localhost/' . $suffix);

			$route = $router->matchRequest($request);
			$this->assertInstanceOf(ControllerRoute::class, $route, $route::class . ' ' . $route->getPattern());

			[
				$first,
				$second,
			] = explode('/', $suffix, 3) + [
				'',
				'',
			];
			$response = $route->execute($request);
			if (str_starts_with($suffix, $testRouteStart)) {
				$remain = ltrim(StringTools::removePrefix($suffix, $testRouteStart), '/') . '//';

				$this->assertNull($response->content(), "Testing $suffix content to be null");
				$middlePart = '';
			} else {
				$middlePart = "$first|$second";
				$this->assertEquals($middlePart, $response->content());
				$remain = $suffix;
			}
			$expected[] = $remain ? array_slice(explode('/', $remain, 3), 0, 2) : [
				'',
				'',
			];
			$response->setPageTheme('test-theme');

			$renderedContent = $response->render([Response::OPTION_SKIP_HEADERS => true, ]);
			$this->assertEquals("--Test--\n$middlePart\nstring\nEOF", $renderedContent);
		}


		$this->assertEquals($expected, TestController::getOption($this->application));
	}
}
