<?php

/**
 * Add developer tools and expose items based on IP
 * 
 * @author kent
 *
 */
class Module_Developer extends Module implements Module_Interface_Routes {
	public function test_ip() {
		$application = $this->application;
		$ips = $this->option_list('ip_allow');
		$development = null;
		$ip = IPv4::remote();
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
			if (IPv4::within_network($ip, $mask)) {
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
		if (self::ip_matches($this->option_list('ip_deny'))) {
			$development = false;
		}
		if ($development !== null) {
			$application->development($development);
		}
	}
	private static function ip_matches(array $ips) {
		$ip = IPv4::remote();
		foreach ($ips as $mask) {
			if ($ip === $mask || IPv4::within_network($ip, $mask)) {
				return true;
			}
		}
		return false;
	}
	public function router_prematch() {
		$app = $this->application;
		$restricted_ips = $this->option_list('ip_restrict');
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
	public function initialize() {
		$zesk = $this->zesk;
		
		$zesk->hooks->add("Application::configured", array(
			$this,
			'test_ip'
		));
		$zesk->hooks->add("Application::router_prematch", array(
			$this,
			'router_prematch'
		));
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
			'method' => 'IPv4::remote',
			'json' => true
		) + $extras);
		$router->add_route('development/includes', array(
			'method' => array(
				$this,
				'development_includes'
			),
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
			// TODO Potentical security issue - pass in URL value here
			$db = $this->application->database_factory($arg);
		} else {
			$db = $this->application->database_factory();
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
	public function development_includes() {
		return get_included_files();
	}
}

