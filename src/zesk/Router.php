<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Psr\Cache\InvalidArgumentException;
use stdClass;
use zesk\Exception\ClassNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Router\Parser;

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
 * "redirect" After invocation, redirect to here
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
class Router extends Hookable
{
	/**
	 *
	 */
	public const HOOK_ROUTER_DERIVED_CLASSES = 'routerDerivedClasses';

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
	protected string $applicationClass = '';

	/**
	 *
	 * @var array of class => Route
	 */
	protected array $reverseRoutes = [];

	/**
	 *
	 * @var Route[]
	 */
	protected array $routes = [];

	/**
	 *
	 * @var array of Route
	 */
	protected array $byId = [];

	/**
	 *
	 * @var string
	 */
	protected string $prefix = '/';

	/**
	 *
	 * @var string
	 */
	protected string $defaultRoute = '';

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
	protected int $weightIndex = 0;

	/**
	 *
	 *
	 */
	public function __serialize(): array
	{
		return [
			'applicationClass' => $this->applicationClass, 'reverseRoutes' => $this->reverseRoutes,
			'routes' => $this->routes, 'prefix' => $this->prefix, 'defaultRoute' => $this->defaultRoute,
			'aliases' => $this->aliases,
		] + parent::__serialize();
	}

	/**
	 */
	public function __unserialize(array $data): void
	{
		parent::__unserialize($data);
		$this->applicationClass = $data['applicationClass'];
		$this->reverseRoutes = $data['reverseRoutes'];
		$this->routes = $data['routes'];
		$this->prefix = $data['prefix'];
		$this->defaultRoute = $data['defaultRoute'];
		$this->aliases = $data['aliases'];
		$this->byId = [];
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
	 * @throws ClassNotFound
	 */
	public static function factory(Application $application, array $options = []): self
	{
		$result = $application->factory(__CLASS__, $application, $options);
		assert($result instanceof self);
		return $result;
	}

	/**
	 * Router constructor.
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = [])
	{
		parent::__construct($application, $options);
		$this->applicationClass = $application::class;
	}

	/**
	 * Cache this router
	 * @throws NotFoundException
	 */
	public function cache(string $id): self
	{
		try {
			$item = $this->application->cacheItemPool()->getItem(__CLASS__);
		} catch (InvalidArgumentException $e) {
			throw new NotFoundException($e->getMessage());
		}
		$value = new stdClass();
		$value->id = $id;
		$value->router = $this;
		$this->application->cacheItemPool()->saveDeferred($item->set($value));
		return $this;
	}

	/**
	 * Whether this router is cached; performance optimization
	 *
	 * @param string $id
	 * @return Router
	 * @throws NotFoundException
	 */
	public function cached(string $id = ''): Router
	{
		try {
			$item = $this->application->cacheItemPool()->getItem(__CLASS__);
		} catch (InvalidArgumentException $e) {
			throw new NotFoundException($e->getMessage());
		}
		if (!$item->isHit()) {
			throw new NotFoundException('Not cached');
		}
		$value = $item->get();
		if ($id !== '') {
			if ($id !== $value->id) {
				throw new NotFoundException('Cache invalid');
			}
		}
		return $value->router;
	}

	/**
	 */
	private function _sort(): void
	{
		uasort($this->routes, Route::compareWeight(...));
	}

	/**
	 * Fetch a route by ID.
	 * ID is an attribute associated with each route, or the clean URL.
	 *
	 * @param string $id
	 * @return Route
	 * @throws KeyNotFound
	 */
	public function route(string $id): Route
	{
		$key = strtolower($id);
		if (array_key_exists($key, $this->byId)) {
			return $this->byId[$key];
		}

		throw new KeyNotFound('Route not found {id} (key: {key})', ['id' => $id, 'key' => $key]);
	}

	/**
	 *
	 * @return Route[]
	 */
	public function routes(): array
	{
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
	public function setPrefix(string $set): self
	{
		$this->prefix = $set;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function prefix(): string
	{
		return $this->prefix;
	}

	/**
	 * Direct mapping of path -> new path during match
	 *
	 * @param string $from
	 * @param string $to - The ID of the route to match
	 * @return $this
	 */
	public function addAlias(string $from, string $to): self
	{
		$this->aliases[$from] = $to;
		return $this;
	}

	/**
	 * @param $level
	 * @param $message
	 * @param array $arguments
	 * @return void
	 */
	public function log($level, $message, array $arguments = []): void
	{
		if ($this->debug) {
			$this->application->log($level, $message, $arguments);
		}
	}

	/**
	 * Match a request to this router. Return a Route. Returned route will have ->request() set to this object.
	 *
	 * @param string $path
	 * @param string $method
	 * @return Route
	 * @throws NotFoundException
	 */
	public function match(string $path, string $method = HTTP::METHOD_GET): Route
	{
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

		throw new NotFoundException('No match for {path} ({method})', ['method' => $method, 'path' => $path]);
	}

	/**
	 * Match a request to this router. Return a Route. Returned route will have ->request() set to this object.
	 *
	 * @param Request $request
	 * @return Route
	 * @throws NotFoundException
	 */
	public function matchRequest(Request $request): Route
	{
		return $this->match($request->path(), $request->method());
	}

	/**
	 * Add a route
	 *
	 * @param string $path
	 * @param array $options
	 * @return Route
	 * @throws ClassNotFound
	 * @throws SemanticsException
	 */
	public function addRoute(string $path, array $options): Route
	{
		if ($path === '.') {
			$this->defaultRoute = $path;
			$path = '';
		}
		if (!array_key_exists('weight', $options)) {
			$options['weight'] = ($this->weightIndex++) / 1000;
		}
		$this->sorted = false;
		return $this->routes[$path] = $this->_addRouteID($this->_registerRoute(Route::factory($this, $path, $options)));
	}

	/**
	 *
	 * @param Route $route
	 * @return Route
	 * @throws SemanticsException
	 */
	private function _addRouteID(Route $route): Route
	{
		$id = $route->id();
		if (!$id) {
			$id = $route->getPattern();
		}
		$this->byId[strtolower($id)] = $route;
		$route->wasAdded($this);
		return $route;
	}

	/**
	 * Load a Router file
	 *
	 * @param string $contents
	 * @param array $add_options
	 * @return void
	 * @throws SyntaxException
	 * @see Parser
	 */
	public function import(string $contents, array $add_options = []): void
	{
		$parser = new Parser($contents);
		$parser->execute($this, $add_options);
	}

	/**
	 * @param Route $route
	 * @return Route
	 */
	private function _registerRoute(Route $route): Route
	{
		$classActions = $route->classActions();
		if (!$classActions) {
			return $route;
		}
		foreach ($classActions as $class => $actions) {
			$class = $this->application->objects->resolve($class);
			foreach ($actions as $action) {
				$this->reverseRoutes[strtolower($class)][strtolower($action)][] = $route;
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
	 * @throws NotFoundException
	 */
	private function _findRoute(array $routes, string $action, Model $object = null, array $options = []): string
	{
		$options = Types::toArray($options);
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

		throw new NotFoundException('Can not find route for {action} {object}', [
			'action' => $action, 'object' => $object,
		]);
	}

	/**
	 * @param Model $object
	 * @return array
	 */
	private function derivedClasses(Model $object): array
	{
		$by_class = [];
		return $object->invokeTypedFilters(self::HOOK_ROUTER_DERIVED_CLASSES, [
			$by_class,
		], [$this]);
	}

	public const FILTER_ROUTE_OPTIONS = self::class . '::routeOptions';

	public const FILTER_GET_ROUTE_ALTER = self::class . '::getRouteAlter';

	public const HOOK_GET_ROUTE = self::class . '::getRoute';

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
	 * @throws NotFoundException
	 */
	public function getRoute(string $action, string|array|Model $object = null, string|array $options = []): string
	{
		$original_action = $action;
		$app = $this->application;
		if (is_string($options)) {
			$options = [
				'query' => URL::queryParse(str_starts_with($options, '?') ? substr($options, 1) : $options),
			];
		}
		$route = $options['currentRoute'] ?? null;
		if ($route instanceof Route) {
			$options += $route->argumentsNamed();
		}
		if ($object) {
			$try_classes = $app->classes->hierarchy($object, Model::class);
			$options += $object->invokeTypedFilters(self::FILTER_ROUTE_OPTIONS, $options, [$this, $action]);
			$options['derivedClasses'] += $this->derivedClasses($object);
		} elseif (is_string($object)) {
			$try_classes = [
				$object,
			];
		} elseif (is_array($object)) {
			$try_classes = $object;
		}
		$lowAction = strtolower($action);
		if (array_key_exists($lowAction, $this->byId)) {
			$route = $this->byId[$lowAction];
			/* @var $route Route */
			return $route->getRoute($action, $object, $options);
		}
		$try_classes[] = '*';
		foreach ($try_classes as $try_class) {
			foreach ([
				$action, '*',
			] as $try_action) {
				if ($try_class !== '*') {
					$try_class = strtolower($app->objects->resolve($try_class));
				}
				$try_actions = $this->reverseRoutes[$try_class] ?? null;
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
					$url = $this->invokeTypedFilters(self::FILTER_GET_ROUTE_ALTER, $url, [
						$action, $object, $options,
					]);
					return $this->prefix . $url;
				} catch (NotFoundException) {
					/* Pass */
				}
			}
		}
		$url = $this->invokeHooksUntil(self::HOOK_GET_ROUTE, [
			$action, $object, $options,
		]);
		if (empty($url)) {
			throw new NotFoundException('No reverse route for {classes}->{action} {backtrace}', [
				'classes' => $try_classes, 'action' => $original_action, 'backtrace' => Kernel::backtrace(),
			]);
		}
		return URL::queryAppend($this->prefix . $url, $options ['query'] ?? []);
	}

	/**
	 * Retrieve a list of all controllers in the router
	 *
	 * @return array
	 * @throws ClassNotFound
	 */
	public function controllers(): array
	{
		$controllers = [];
		foreach ($this->routes as $route) {
			if (method_exists($route, 'controller')) {
				$controllers[] = $route->controller();
			}
		}
		return $controllers;
	}
}
