<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use stdClass;
use zesk\Router\Parser;
use Psr\Cache\InvalidArgumentException;

/**
 * @see Route
 *
 * Router
 *
 * Handles converting a URL Path to a method, with a special case for Controller objects.
 * Support for permissions, automatic variable conversion as well.
 *
 * Basic setup is pattern -> array of settings
 *
 * Pattern is a URL path, excluding / prefix , with optional variables included, e.g.
 *
 * /foo/bar/dee
 *
 * You can specify optional sections using parenthesis ()
 *
 * The settings available are:
 *
 * "controller" Controller to invoke, can include variable name
 * "controller prefixes" A list of controller prefixes to use, overrides the default Router prefixes
 * "controller options" Array of options to set on the controller upon creation
 * "action" The action to invoke in the controller
 * "arguments" The arguments to the action. If values are numeric, specifies the URL part to pass
 * as an argument. Keys may be specified, but are ignored.
 *
 * For a template, it's:
 *
 * "template" Template to load, may include variables names
 * "arguments" Name/value pairs passed to the template. If values are numeric, specifies the URL
 * part to pass as an argument.
 *
 * For a method it's:
 *
 * "method" Name of method to invoke. For class methods, specify array("ClassName" => "method_name"), or
 * ClassName::method_name
 * arguments The arguments to the action. For numeric values, specifies the URL part to pass as
 * an argument.
 *
 * For all routes
 *
 * "content" type Set the response content type to this
 * "status code" Set the response status code
 * "status message" Set the response status message
 * "redirect" After invokation, redirect to here
 *
 * The pattern syntax is:
 *
 * {variable-name} An untyped variable, or string.
 * {type variable-name} Typed variable name.
 * {type} An unnamed variable, can be referenced by the URL path order
 *
 * Type can be:
 * - integer
 * - double
 * - string
 * - array
 * - list
 * - comma-list
 * - dash-list
 * - semicolon-list
 * - option
 * - or a class
 *
 */
class Router extends Hookable {
	/**
	 * Debugging is enabled
	 *
	 * @var boolean
	 */
	public bool $debug = false;

	/**
	 *
	 * @var string
	 */
	protected string $application_class = '';

	/**
	 *
	 * @var array of class => Route
	 */
	protected array $reverse_routes = [];

	/**
	 *
	 * @var Route[]
	 */
	protected array $routes = [];

	/**
	 *
	 * @var array of Route
	 */
	protected array $by_id = [];

	/**
	 *
	 * @var string
	 */
	protected string $prefix = '/';

	/**
	 *
	 * @var string
	 */
	protected string $default_route = '';

	/**
	 *
	 * @var array
	 */
	protected array $aliases = [];

	/**
	 * Whether the routes have been sorted by weight yet
	 *
	 * @var boolean
	 */
	private bool $sorted = false;

	/**
	 * Index to ensure routes are sorted by added order.
	 */
	protected int $weight_index = 0;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Options::__sleep()
	 */
	public function __sleep() {
		return array_merge([
			'application_class', 'reverse_routes', 'routes', 'prefix', 'default_route', 'aliases',
		], parent::__sleep());
	}

	/**
	 */
	public function __wakeup(): void {
		parent::__wakeup();
		$this->by_id = [];
		foreach ($this->routes as $route) {
			$route->wakeupConnect($this);
		}
		$this->sorted = false;
	}

	/**
	 * Create a new Router
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 * @throws Exception_Class_NotFound
	 */
	public static function factory(Application $application, array $options = []): self {
		$result = $application->factory(__CLASS__, $application, $options);
		assert($result instanceof self);
		return $result;
	}

	/**
	 * Router constructor.
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->application_class = $application::class;
	}

	/**
	 * Cache this router
	 * @throws Exception_NotFound
	 */
	public function cache(string $id): self {
		try {
			$item = $this->application->cache->getItem(__CLASS__);
		} catch (InvalidArgumentException $e) {
			throw new Exception_NotFound($e->getMessage());
		}
		$value = new stdClass();
		$value->id = $id;
		$value->router = $this;
		$this->application->cache->saveDeferred($item->set($value));
		return $this;
	}

	/**
	 * Whether this router is cached; performance optimization
	 *
	 * @param string $id
	 * @return Router
	 * @throws Exception_NotFound
	 */
	public function cached(string $id = ''): Router {
		try {
			$item = $this->application->cache->getItem(__CLASS__);
		} catch (InvalidArgumentException $e) {
			throw new Exception_NotFound($e->getMessage());
		}
		if (!$item->isHit()) {
			throw new Exception_NotFound('Not cached');
		}
		$value = $item->get();
		if ($id !== '') {
			if ($id !== $value->id) {
				throw new Exception_NotFound('Cache invalid');
			}
		}
		return $value->router;
	}

	/**
	 */
	private function _sort(): void {
		uasort($this->routes, Route::compareWeight(...));
	}

	/**
	 * Fetch a route by ID.
	 * ID is an attribute associated with each route, or the clean URL.
	 *
	 * @param string $id
	 * @return Route
	 * @throws Exception_Key
	 */
	public function route(string $id): Route {
		$key = strtolower($id);
		if (array_key_exists($key, $this->by_id)) {
			return $this->by_id[$key];
		}

		throw new Exception_Key('Route not found {id} (key: {key})', ['id' => $id, 'key' => $key]);
	}

	/**
	 *
	 * @return Route[]
	 */
	public function routes(): array {
		if (!$this->sorted) {
			$this->_sort();
			$this->sorted = true;
		}
		return $this->routes;
	}

	/**
	 *
	 * @param string $set
	 * @return self
	 */
	public function setPrefix(string $set): self {
		$this->prefix = $set;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function prefix(): string {
		return $this->prefix;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @return $this
	 */
	public function addAlias(string $from, string $to): self {
		$this->aliases[$from] = $to;
		return $this;
	}

	/**
	 * @param $level
	 * @param $message
	 * @param array $arguments
	 * @return void
	 */
	public function log($level, $message, array $arguments = []): void {
		if ($this->debug) {
			$this->application->logger->log($level, $message, $arguments);
		}
	}

	/**
	 * Match a request to this router. Return a Route. Returned route will have ->request() set to this object.
	 *
	 * @param string $path
	 * @param string $method
	 * @return Route
	 * @throws Exception_NotFound
	 */
	public function match(string $path, string $method = HTTP::METHOD_GET): Route {
		if ($this->prefix) {
			$path = StringTools::removePrefix($path, $this->prefix);
		}
		$path = $this->aliases[$path] ?? $path;
		foreach ($this->routes() as $route) {
			if ($route->match($path, $method)) {
				$this->log('debug', 'Matched {path} to {route}', compact('path', 'route'));
				return $route;
			}
		}

		throw new Exception_NotFound('No match for {path} ({method})', ['method' => $method, 'path' => $path]);
	}

	/**
	 * Match a request to this router. Return a Route. Returned route will have ->request() set to this object.
	 *
	 * @param Request $request
	 * @return Route
	 * @throws Exception_NotFound
	 */
	public function matchRequest(Request $request): Route {
		return $this->match($request->path(), $request->method());
	}

	/**
	 * Add a route
	 *
	 * @param string $path
	 * @param array $options
	 * @return Route
	 */
	public function addRoute(string $path, array $options): Route {
		if ($path === '.') {
			$this->default_route = $path;
			$path = '';
		}
		if (!array_key_exists('weight', $options)) {
			$options['weight'] = ($this->weight_index++) / 1000;
		}
		$this->sorted = false;
		return $this->routes[$path] = $this->_addRouteID($this->_registerRoute(Route::factory($this, $path, $options)));
	}

	/**
	 *
	 * @param Route $route
	 * @return Route
	 */
	private function _addRouteID(Route $route): Route {
		$id = $route->option('id');
		if (!$id) {
			$id = $route->getPattern();
		}
		$this->by_id[strtolower($id)] = $route;
		return $route;
	}

	/**
	 * Load a Router file
	 *
	 * @param string $contents
	 * @param array $add_options
	 * @return void
	 * @throws Exception_Syntax
	 * @see Parser
	 */
	public function import(string $contents, array $add_options = []): void {
		$parser = new Parser($contents);
		$parser->execute($this, $add_options);
	}

	/**
	 * @param Route $route
	 * @return Route
	 */
	private function _registerRoute(Route $route): Route {
		$classActions = $route->classActions();
		if (!$classActions) {
			return $route;
		}
		foreach ($classActions as $class => $actions) {
			$class = $this->application->objects->resolve($class);
			foreach ($actions as $action) {
				$this->reverse_routes[strtolower($class)][strtolower($action)][] = $route;
			}
		}
		return $route;
	}

	/**
	 * @param array $routes
	 * @param string $action
	 * @param Model|null $object
	 * @param array $options
	 * @return Route
	 * @throws Exception_NotFound
	 */
	private function _findRoute(array $routes, string $action, Model $object = null, array $options = []): string {
		$options = toArray($options);
		foreach ($routes as $route) {
			assert($route instanceof Route);
			$url = $route->getRoute($action, $object, $options);
			if ($url) {
				if (array_key_exists('query', $options)) {
					return URL::queryAppend($url, $options['query']);
				}
				return $url;
			}
		}

		throw new Exception_NotFound('Can not find route for {action} {object}', [
			'action' => $action, 'object' => $object,
		]);
	}

	/**
	 *
	 * @param array $by_class
	 * @param Model $add
	 * @param string $stop_class
	 * @return array
	 */
	public static function add_derived_classes(array $by_class, Model $add, string $stop_class = ''): array {
		$id = $add->id();
		foreach ($add->application->classes->hierarchy($add, $stop_class ?: Model::class) as $class) {
			$by_class[$class] = $id;
		}
		return $by_class;
	}

	private function derived_classes(Model $object) {
		$by_class = [];
		return $object->callHookArguments('router_derived_classes', [
			$by_class,
		], $by_class);
	}

	/**
	 * Retrieve a route to an object from the router.
	 * Uses current route's context to determine new route.
	 *
	 * @param string $action
	 * @param null|string|array|Model $object An instance of a Model, a name of a model class, a name of a Controller
	 * class
	 * @param array|string $options
	 *            "query" => (string or array). Append query string to URL
	 *            "inherit_current_route" => (boolean). Use variables from current route when
	 *            generating this route.
	 *
	 * @return string
	 * @throws Exception_NotFound
	 */
	public function getRoute(string $action, string|array|Model $object = null, string|array $options = []): string {
		$original_action = $action;
		$app = $this->application;
		if (is_string($options) && str_starts_with($options, '?')) {
			$options = [
				'query' => URL::queryParse($options),
			];
		}
		$options = toArray($options);
		$route = $options['current_route'] ?? null;
		if ($route instanceof Route) {
			$options += $route->argumentsNamed();
		}
		if ($object) {
			$try_classes = $app->classes->hierarchy($object, Model::class);
			$options += $object->callHookArguments('route_options', [
				$this, $action,
			], []) + [
				'derived_classes' => [],
			];
			$options['derived_classes'] += $this->derived_classes($object);
		} elseif (is_string($object)) {
			$try_classes = [
				$object,
			];
		} elseif (is_array($object)) {
			$try_classes = $object;
		}
		$try_classes[] = '*';
		foreach ($try_classes as $try_class) {
			foreach ([
				$action, '*',
			] as $try_action) {
				if ($try_class !== '*') {
					$try_class = strtolower($app->objects->resolve($try_class));
				}
				$try_actions = $this->reverse_routes[$try_class] ?? null;
				if (!is_array($try_actions)) {
					continue;
				}
				$try_action = strtolower($try_action);
				$try_routes = $try_actions[$try_action] ?? null;
				if (!is_array($try_routes)) {
					continue;
				}

				try {
					$url = $this->_findRoute($try_routes, $action, $object, $options);
					$url = $app->hooks->callArguments(__CLASS__ . '::getRoute_alter', [
						$action, $object, $options,
					], $url);
					return $this->prefix . $url;
				} catch (Exception_NotFound) {
					/* Pass */
				}
			}
		}
		$url = $this->callHookArguments('getRoute', [
			$action, $object, $options,
		]);
		if (empty($url)) {
			throw new Exception_NotFound('No reverse route for {classes}->{action} {backtrace}', [
				'classes' => $try_classes, 'action' => $original_action, 'backtrace' => _backtrace(),
			]);
		}
		return URL::queryAppend($this->prefix . $url, $options ['query'] ?? []);
	}

	/**
	 * Retrieve a list of all known controllers
	 *
	 * @return array
	 * @throws Exception_Class_NotFound
	 */
	public function controllers(): array {
		$controllers = Controller::all($this->application);
		$result = [];
		$objects = $this->application->objects;
		foreach ($controllers as $controller => $settings) {
			$result[$controller] = $objects->factory($controller, $this->application);
		}
		return $result;
	}
}
