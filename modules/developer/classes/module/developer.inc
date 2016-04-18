<?php

/**
 * Add developer tools and expose items based on IP
 * 
 * @author kent
 *
 */
class Module_Developer extends Module implements Module_Interface_Routes {
	public static function test_ip(Application $application) {
		$ips = zesk::geta('Module_Developer::ip_allow');
		$development = false;
		$ip = ip::remote();
		foreach ($ips as $mask) {
			if ($ip === $mask) {
				log::debug("{class}::{function}: {ip} === {mask}, development on", array(
					"class" => __CLASS__,
					"function" => __FUNCTION__,
					"ip" => $ip,
					"mask" => $mask
				));
				$development = true;
				break;
			}
			if (ip::within_network($ip, $mask)) {
				log::debug("{class}::{function}: {ip} within network {mask}, development on", array(
					"class" => __CLASS__,
					"function" => __FUNCTION__,
					"ip" => $ip,
					"mask" => $mask
				));
				$development = true;
				break;
			}
		}
		if (self::ip_matches(zesk::geta('Module_Developer::ip_deny'))) {
			$application->development(false);
			return;
		}
		if ($development) {
			$application->development($development);
		}
	}
	private static function ip_matches(array $ips) {
		$ip = ip::remote();
		foreach ($ips as $mask) {
			if ($ip === $mask || ip::within_network($ip, $mask)) {
				return true;
			}
		}
		return false;
	}
	public static function router_prematch(Application $app, Router $router) {
		$restricted_ips = zesk::geta('Module_Developer::ip_restrict');
		if (count($restricted_ips) === 0) {
			return;
		}
		if (!self::ip_matches($restricted_ips)) {
			if (begins($app->request->path(), "/share")) {
				return;
			}
			$app->request->path("/developer/forbidden");
		}
	}
	
	/**
	 * 
	 */
	public static function hooks() {
		zesk::add_hook("Application::configured", __CLASS__ . '::test_ip');
		zesk::add_hook("Application::router_prematch", __CLASS__ . '::router_prematch');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module_Interface_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		if (!$this->application->development()) {
			return;
		}
		$extras = array(
			'permission' => 'debug'
		);
		$extras = array(
			'weight' => 'first'
		);
		if (function_exists('phpinfo')) {
			// Some installations disable this function for security
			$router->add_route('phpinfo', array(
				'method' => 'phpinfo',
				'buffer' => true
			) + $extras);
		}
		$router->add_route('debug', array(
			'theme' => 'system/debug'
		) + $extras);
		$router->add_route('developer/forbidden', array(
			'theme' => 'developer/forbidden'
		) + $extras);
		$router->add_route('system-status', array(
			'theme' => 'system/status'
		) + $extras);
		$router->add_route('routes', array(
			'theme' => 'system/routes'
		) + $extras);
		$router->add_route('ip', array(
			'method' => 'ip::remote',
			'json' => true
		) + $extras);
		$router->add_route('development', array(
			'method' => array(
				$this->application,
				'development'
			),
			'json' => true
		) + $extras);
		$router->add_route('session', array(
			'method' => array(
				$this,
				'dump_session'
			),
			'arguments' => "{response}"
		) + $extras);
		$router->add_route('router', array(
			'method' => array(
				$this,
				'dump_router'
			),
			'arguments' => "{request}"
		) + $extras);
		$router->add_route('schema(/*)', array(
			'method' => array(
				$this,
				'schema'
			),
			'arguments' => array(
				"{application}",
				"{request}",
				'{response}',
				1
			)
		) + $extras);
	}
	
	/**
	 * 
	 * @param Response $response
	 */
	public function dump_session(Response $response) {
		$session = Session::instance();
		$response->json($session->get());
	}
	
	/**
	 * 
	 * @param Request $request
	 */
	public function dump_router(Request $request) {
		$router = $request->router;
		/* @var $route Route */
		foreach ($router->routes() as $pattern => $route) {
			echo html::tag('h2', $route->clean_pattern) . theme('dl', array(
				'class' => get_class($route)
			) + $route->option());
		}
	}
	
	/**
	 * 
	 * @param Application $app
	 * @param Request $request
	 * @param Response_HTML $response
	 * @param unknown $arg
	 */
	public function schema(Application $app, Request $request, Response_HTML $response, $arg = null) {
		Database_Schema::debug(true);
		$db = null;
		if ($arg) {
			$db = Database::factory($arg);
		} else {
			$db = Database::instance();
		}
		$results = $app->schema_synchronize($db);
		$exception = null;
		if ($request->get_bool("go")) {
			try {
				foreach ($results as $index => $sql) {
					$db->query($results);
					$results[$index] = html::tag('span', '.alert alert-success', $results[$index]);
				}
			} catch (Exception $exception) {
				$results[$index] = html::tag('span', '.alert alert-danger', $results[$index]);
			}
		}
		$result = $exception ? theme('exception', array(
			'content' => $exception
		)) : "";
		$result .= html::tag('ul', ".sql", html::tags('li', array_merge(array(
			"-- " . $arg . ";\n"
		), arr::suffix($results, ";\n"))));
		return $result;
	}
}

