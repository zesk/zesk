<?php
declare(strict_types=1);

namespace zesk;

use zesk\Route\Controller as ControllerRoute;

class CTest extends Controller {
	protected array $argumentMethods = [];

	protected array $actionMethods = [
		'action_{action}', 'action_index',
	];

	protected array $beforeMethods = [

	];

	protected array $afterMethods = [

	];

	public function initialize(): void {
		parent::initialize();
		$this->application->setOption(__CLASS__, []);
		$this->application->optionAppend(__CLASS__, 'init');
	}

	public function action_index(string $a = '', string $b = ''): string {
		$this->application->optionAppend(__CLASS__, [$a, $b]);
		return "$a|$b";
	}

	public function action_array(string $a, string $b, Request $request): array {
		$this->application->optionAppend(__CLASS__, [$a, $b]);
		return ['first' => $a, 'second' => $b, 'request' => $request];
	}

	public static function getOption(Application $app): array {
		return $app->optionArray(__CLASS__);
	}
}

class ControllerTest extends UnitTest {
	/**
	 *
	 */
	public function test_Controller(): void {
		$app = $this->application;

		$app->addThemePath($this->test_sandbox());
		$themeContent =  "--Test--\n";
		$themeContent .= '<' . "?php\n";
		$themeContent .= "echo \"\$content\n\";\n";
		$themeContent .= "echo gettype(\$content) . \"\n\";\n";
		$themeContent .= "?>\nEOF";

		File::put($this->test_sandbox('test-theme.tpl'), $themeContent);

		$app->setOption('cache_router', false);
		$router = $app->router();

		$router->addRoute('({first}(/{second}))', [
			'controller' => Ctest::class, 'arguments' => [0, 1], 'action' => 'index', 'default' => true, 'weight' => 1,
		]);
		$testRouteStart = 'array'; /* No slashes allowed - indexes 1,2 */
		$router->addRoute($testRouteStart . '(/{what}(/{verb}))', [
			'controller' => Ctest::class, 'arguments' => [1, 2, '{request}'], 'action' => 'array', 'weight' => -1,
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
			$this->assertInstanceOf(ControllerRoute::class, $route);

			[$first, $second] = explode('/', $suffix, 3) + ['', ''];
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
			$expected[] = $remain ? array_slice(explode('/', $remain, 3), 0, 2) : ['', ''];
			$this->assertInstanceOf(Response::class, $response);
			$response->setPageTheme('test-theme');

			$renderedContent = $response->render([Response::OPTION_SKIP_HEADERS => true, ]);
			$this->assertEquals("--Test--\n$middlePart\nstring\nEOF", $renderedContent);
		}


		$this->assertEquals($expected, CTest::getOption($this->application));
	}
}
