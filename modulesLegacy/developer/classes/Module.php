<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\Developer;

use zesk\ORM_Schema;
use zesk\ArrayTools;
use zesk\Application;
use zesk\HTML;
use zesk\Interface_Module_Routes;
use zesk\IPv4;
use zesk\Net_HTTP;
use zesk\Response;
use zesk\Request;
use zesk\Router;
use zesk\Exception;

/**
 * Add developer tools and expose items based on IP
 *
 * @author kent
 *
 */
class Module extends \zesk\Module implements Interface_Module_Routes {
	/**
	 * List of request parameters/HTTP headers to allow for mocking AJAX and other types of
	 * calls for debugging and/or testing
	 *
	 * @var array
	 */
	public static $allowed_mock_headers = [
		'mock_accept' => HTTP::REQUEST_ACCEPT,
	];

	/**
	 *
	 * @param Application $application
	 * @param Request $request
	 */
	public function test_ip(Application $application, Request $request): void {
		$application = $this->application;
		$ips = $this->optionIterable('ip_allow');
		$development = null;
		$ip = $request->ip();
		foreach ($ips as $mask) {
			if ($ip === $mask) {
				$this->application->logger->debug('{class}::{function}: {ip} === {mask}, development on', [
					'class' => __CLASS__,
					'function' => __FUNCTION__,
					'ip' => $ip,
					'mask' => $mask,
				]);
				$development = true;

				break;
			}
			if (IPv4::within_network($ip, $mask)) {
				$this->application->logger->debug('{class}::{function}: {ip} within network {mask}, development on', [
					'class' => __CLASS__,
					'function' => __FUNCTION__,
					'ip' => $ip,
					'mask' => $mask,
				]);
				$development = true;

				break;
			}
		}
		if ($this->ip_matches($ip, $this->optionIterable('ip_deny'))) {
			$development = false;
		}
		if ($development !== null) {
			$application->setDevelopment($development);
		}
	}

	/**
	 * Does the remote IP address match one of the list of IPs including netmasks?
	 *
	 * @param array $ips
	 * @return boolean
	 */
	private function ip_matches($ip, array $ips) {
		foreach ($ips as $mask) {
			if ($ip === $mask || IPv4::within_network($ip, $mask)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Modify the Request to allow for mock headers
	 *
	 * @param Request $request
	 */
	private function handle_mock_headers(Request $request): void {
		foreach (self::$allowed_mock_headers as $request_parameter => $request_header) {
			if ($request->has($request_parameter)) {
				$request->header($request_header, $request->get($request_parameter));
			}
		}
	}

	/**
	 * Before matching, modify path in case request is prohibited
	 *
	 * @param Application $application
	 * @param Router $router
	 * @param Request $request
	 */
	public function router_prematch(Application $application, Router $router, Request $request): void {
		$app = $this->application;
		$this->handle_mock_headers($request);
		$restricted_ips = $this->optionIterable('ip_restrict');
		if (count($restricted_ips) === 0) {
			return;
		}
		if (!$this->ip_matches($request->ip(), $restricted_ips)) {
			if (str_starts_with($request->path(), '/share')) {
				return;
			}
			$request->path('/developer/forbidden');
		}
	}

	public function initialize(): void {
		$app = $this->application;

		$app->hooks->add('zesk\\Application::main', [
			$this,
			'test_ip',
		]);
		$app->hooks->add('zesk\\Application::router_prematch', [
			$this,
			'router_prematch',
		]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		if (!$this->application->development()) {
			return;
		}
		$extras = [
			'permission' => 'debug',
		];
		$extras = [
			'weight' => 'first',
		];
		if (function_exists('phpinfo')) {
			// Some installations disable this function for security
			$router->addRoute('developer/phpinfo', [
				'method' => 'phpinfo',
				'buffer' => true,
			] + $extras);
		} else {
			$router->addRoute('developer/phpinfo', [
				'content' => 'phpinfo function is disabled (usually for security)',
			]);
		}
		$router->addRoute('developer/opcache_get_configuration', [
			'method' => 'opcache_get_configuration',
			'json' => true,
		]);
		$router->addRoute('developer/opcache_get_status', [
			'method' => 'opcache_get_status',
			'arguments' => [
				false,
			],
			'json' => true,
		]);
		$router->addRoute('developer/debug', [
			'theme' => 'system/debug',
		] + $extras);
		$router->addRoute('developer/forbidden', [
			'theme' => 'developer/forbidden',
		] + $extras);
		$router->addRoute('system-status', [
			'theme' => 'system/status',
		] + $extras);
		$router->addRoute('developer/routes', [
			'theme' => 'system/routes',
		] + $extras);
		$router->addRoute('developer/modules', [
			'theme' => 'system/modules',
		] + $extras);
		$router->addRoute('developer/ip', [
			'method' => [
				$this,
				'developer_ip',
			],
			'json' => true,
		] + $extras);
		$router->addRoute('development/includes', [
			'method' => [
				$this,
				'development_includes',
			],
			'json' => true,
		] + $extras);
		$router->addRoute('developer/development', [
			'method' => [
				$this->application,
				'development',
			],
			'json' => true,
		] + $extras);
		$router->addRoute('developer/session', [
			'method' => [
				$this,
				'dump_session',
			],
			'arguments' => [
				'{application}',
				'{response}',
			] + $extras,
		]);
		$router->addRoute('developer/router', [
			'method' => [
				$this,
				'dump_router',
			],
			'arguments' => '{router}',
		] + $extras);
		$router->addRoute('developer/schema(/*)', [
			'method' => [
				$this,
				'schema',
			],
			'arguments' => [
				'{application}',
				'{request}',
				'{response}',
				1,
			],
		] + $extras);
	}

	/**
	 *
	 * @param Response $response
	 */
	public function dump_session(Application $app, Response $response): void {
		$session = $app->session($app->request());
		$response->json()->setData($session->get());
	}

	/**
	 *
	 * @param Request $request
	 */
	public function dump_router(Router $router): void {
		foreach ($router->routes() as $pattern => $route) {
			echo HTML::tag('h2', $route->clean_pattern) . $this->application->theme('dl', [
				'content' => [
					'class' => $route::class,
				] + $route->options(),
			]);
		}
	}

	/**
	 *
	 * @param Application $app
	 * @param Request $request
	 * @param Response $response
	 * @param unknown $arg
	 */
	public function schema(Application $app, Request $request, Response $response, $arg = null) {
		ORM_Schema::debug(true);
		$db = null;
		$classes = null;
		if ($request->has('classes')) {
			$classes = to_list($request->get('classes'));
		}
		if ($arg) {
			// TODO Potentical security issue - pass in URL value here
			$db = $this->application->databaseRegistry($arg);
		} else {
			$db = $this->application->databaseRegistry();
		}
		$results = $app->ormModule()->schema_synchronize($db, $classes, [
			'follow' => false,
		]);
		$exception = null;
		if ($request->getBool('go')) {
			try {
				foreach ($results as $index => $sql) {
					$db->query($results);
					$results[$index] = HTML::tag('span', '.alert alert-success', $sql);
				}
			} catch (Exception $exception) {
				$results[$index] = HTML::tag('span', '.alert alert-danger', $sql);
			}
		}
		$result = $exception ? $app->theme('exception', [
			'content' => $exception,
		]) : '';
		$result .= HTML::tag('ul', '.sql', HTML::tags('li', [], array_merge([
			'-- ' . $arg . ";\n",
		], ArrayTools::suffixValues($results, ";\n"))));
		return $result;
	}

	public function development_includes() {
		return get_included_files();
	}

	public function developer_ip() {
		return $this->application->request->ip();
	}
}
