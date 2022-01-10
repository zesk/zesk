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
		"mock_accept" => Net_HTTP::REQUEST_ACCEPT,
	];

	/**
	 *
	 * @param Application $application
	 * @param Request $request
	 */
	public function test_ip(Application $application, Request $request): void {
		$application = $this->application;
		$ips = $this->option_list('ip_allow');
		$development = null;
		$ip = $request->ip();
		foreach ($ips as $mask) {
			if ($ip === $mask) {
				$this->application->logger->debug("{class}::{function}: {ip} === {mask}, development on", [
					"class" => __CLASS__,
					"function" => __FUNCTION__,
					"ip" => $ip,
					"mask" => $mask,
				]);
				$development = true;

				break;
			}
			if (IPv4::within_network($ip, $mask)) {
				$this->application->logger->debug("{class}::{function}: {ip} within network {mask}, development on", [
					"class" => __CLASS__,
					"function" => __FUNCTION__,
					"ip" => $ip,
					"mask" => $mask,
				]);
				$development = true;

				break;
			}
		}
		if ($this->ip_matches($ip, $this->option_list('ip_deny'))) {
			$development = false;
		}
		if ($development !== null) {
			$application->development($development);
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
		$restricted_ips = $this->option_list('ip_restrict');
		if (count($restricted_ips) === 0) {
			return;
		}
		if (!$this->ip_matches($request->ip(), $restricted_ips)) {
			if (begins($request->path(), "/share")) {
				return;
			}
			$request->path("/developer/forbidden");
		}
	}

	public function initialize(): void {
		$app = $this->application;

		$app->hooks->add("zesk\\Application::main", [
			$this,
			'test_ip',
		]);
		$app->hooks->add("zesk\\Application::router_prematch", [
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
			$router->add_route('developer/phpinfo', [
				'method' => 'phpinfo',
				'buffer' => true,
			] + $extras);
		} else {
			$router->add_route('developer/phpinfo', [
				'content' => 'phpinfo function is disabled (usually for security)',
			]);
		}
		$router->add_route('developer/opcache_get_configuration', [
			'method' => 'opcache_get_configuration',
			'json' => true,
		]);
		$router->add_route('developer/opcache_get_status', [
			'method' => 'opcache_get_status',
			'arguments' => [
				false,
			],
			'json' => true,
		]);
		$router->add_route('developer/debug', [
			'theme' => 'system/debug',
		] + $extras);
		$router->add_route('developer/forbidden', [
			'theme' => 'developer/forbidden',
		] + $extras);
		$router->add_route('system-status', [
			'theme' => 'system/status',
		] + $extras);
		$router->add_route('developer/routes', [
			'theme' => 'system/routes',
		] + $extras);
		$router->add_route('developer/modules', [
			'theme' => 'system/modules',
		] + $extras);
		$router->add_route('developer/ip', [
			'method' => [
				$this,
				"developer_ip",
			],
			'json' => true,
		] + $extras);
		$router->add_route('development/includes', [
			'method' => [
				$this,
				'development_includes',
			],
			'json' => true,
		] + $extras);
		$router->add_route('developer/development', [
			'method' => [
				$this->application,
				'development',
			],
			'json' => true,
		] + $extras);
		$router->add_route('developer/session', [
			'method' => [
				$this,
				'dump_session',
			],
			'arguments' => [
				"{application}",
				"{response}",
			] + $extras,
		]);
		$router->add_route('developer/router', [
			'method' => [
				$this,
				'dump_router',
			],
			'arguments' => "{router}",
		] + $extras);
		$router->add_route('developer/schema(/*)', [
			'method' => [
				$this,
				'schema',
			],
			'arguments' => [
				"{application}",
				"{request}",
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
		$response->json()->data($session->get());
	}

	/**
	 *
	 * @param Request $request
	 */
	public function dump_router(Router $router): void {
		foreach ($router->routes() as $pattern => $route) {
			echo HTML::tag('h2', $route->clean_pattern) . $this->application->theme('dl', [
				'content' => [
					'class' => get_class($route),
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
		if ($request->has("classes")) {
			$classes = to_list($request->get("classes"));
		}
		if ($arg) {
			// TODO Potentical security issue - pass in URL value here
			$db = $this->application->database_registry($arg);
		} else {
			$db = $this->application->database_registry();
		}
		$results = $app->orm_module()->schema_synchronize($db, $classes, [
			"follow" => false,
		]);
		$exception = null;
		if ($request->get_bool("go")) {
			try {
				foreach ($results as $index => $sql) {
					$db->query($results);
					$results[$index] = HTML::tag('span', '.alert alert-success', $results[$index]);
				}
			} catch (Exception $exception) {
				$results[$index] = HTML::tag('span', '.alert alert-danger', $results[$index]);
			}
		}
		$result = $exception ? $app->theme('exception', [
			'content' => $exception,
		]) : "";
		$result .= HTML::tag('ul', ".sql", HTML::tags('li', array_merge([
			"-- " . $arg . ";\n",
		], ArrayTools::suffix($results, ";\n"))));
		return $result;
	}

	public function development_includes() {
		return get_included_files();
	}

	public function developer_ip() {
		return $this->application->request->ip();
	}
}
