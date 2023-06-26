<?php
declare(strict_types=1);
/**
 * Created on Fri Apr 02 21:04:09 EDT 2010 21:04:09
 *
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Throwable;
use ReflectionClass;
use ReflectionException;

use Psr\Cache\InvalidArgumentException;

use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\Redirect;
use zesk\Interface\Themeable;

/**
 * @see Route
 * @see Router
 * @see Route\Controller
 */
class Controller extends Hookable implements Themeable {
	/**
	 * Default content type for Response generated upon instantiation.
	 *
	 * Can be overridden by setting global "default_content_type" option for this class
	 */
	protected string $default_content_type = '';

	protected array $argumentMethods = [
		'arguments_{METHOD}_{action}', 'arguments_{action}',
	];

	protected array $actionMethods = [
		'action_{METHOD}_{action}', 'action_{action}',
	];

	protected array $beforeMethods = [
		'before_{METHOD}_{action}', 'before_{action}',
	];

	protected array $afterMethods = [
		'after_{METHOD}_{action}', 'after_{action}',
	];

	/**
	 * Router associated with this controller
	 */
	public Router $router;

	/**
	 * Route which brought us here
	 *
	 */
	public Route $route;

	/**
	 * Controller constructor.
	 *
	 * @param Application $app
	 * @param Route $route
	 * @param array $options
	 */
	final public function __construct(Application $app, Route $route, array $options = []) {
		parent::__construct($app, $options);

		// TODO Candidate to remove this and pass from calling factory
		$this->inheritConfiguration();

		$this->route = $route;
		$this->router = $route->router();

		$this->initialize();
		$this->invokeHooks(self::HOOK_INITIALIZE);
	}

	/**
	 *
	 */
	public const HOOK_INITIALIZE = __CLASS__ . '::initialize';

	/**
	 * Shortcut for subclass methods
	 *
	 * @param array|string $types
	 * @param array $arguments
	 * @param array $options
	 * @return string
	 * @throws Redirect
	 */
	public function theme(array|string $types, array $arguments = [], array $options = []): string {
		return $this->application->themes->theme($types, $arguments, $options);
	}

	/**
	 */
	public function classActions(): array {
		return [];
	}

	/**
	 */
	protected function hook_classes(): array {
		return $this->optionIterable('classes');
	}

	public const HOOK_BEFORE = self::class . '::before';

	public const HOOK_AFTER = self::class . '::after';

	/**
	 * Execute this route
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param string $action
	 * @param array $arguments
	 * @return Response
	 * @throws NotFoundException
	 */
	final public function execute(Request $request, Response $response, string $action = '', array $arguments = []): Response {
		$wrapperArguments = [$request, $response, $arguments, $action];
		$app = $this->application;
		$requestMap = [
			'method' => strtolower($request->method()), 'action' => $action ?: 'index',
			'METHOD' => strtoupper($request->method()),
		];
		$beforeMethod = $this->determineMethod($this->beforeMethods, $requestMap, false);
		$argumentsMethod = $this->determineMethod($this->argumentMethods, $requestMap, false);
		$actionMethod = $this->determineMethod($this->actionMethods, $requestMap, true);
		$afterMethod = $this->determineMethod($this->afterMethods, $requestMap, false);
		$__ = $requestMap + [
			'class' => $this::class, 'actionMethod' => $actionMethod,
		];
		$app->debug('Controller {class} running action {method} {action} -> {actionMethod}', $__);

		$this->before($request, $response);
		$this->optionalMethod($beforeMethod, $wrapperArguments);
		$this->invokeHooks(self::HOOK_BEFORE, $wrapperArguments);
		if ($response->status_code !== HTTP::STATUS_OK) {
			return $response;
		}
		$arguments = $this->optionalMethod($argumentsMethod, $wrapperArguments, $arguments);
		if (!is_array($arguments)) {
			throw new NotFoundException("$argumentsMethod did not return an array", [
				'argumentsMethod' => $argumentsMethod,
			]);
		}
		ob_start();
		$result = $this->invokeMethod($actionMethod, $arguments);
		$contents = ob_get_clean();
		$__ = [
			'actionMethod' => $actionMethod, 'bytes' => $contentsLength = strlen($contents), 'contents' => $contents,
			'class' => get_class($this),
		];
		$message = '{actionMethod} output {bytes} bytes: "{contents}"';
		if ($result instanceof Response) {
			$response = $result;
		} elseif (is_string($result)) {
			if ($contentsLength) {
				$app->warning("Incorrect controller semantics: output + return string, output ignored.\n$message", $__);
			}
			$response->setContent($result);
		} elseif (is_array($result)) {
			if ($contentsLength) {
				$app->warning("Incorrect controller semantics: output + return array, output ignored\n$message", $__);
			}
			$response->setResponseData($result);
		} elseif ($contentsLength) {
			if (!$this->optionBool('captureOutput')) {
				$app->warning("{class}::captureOutput is not enabled: $message", $__);
			} else {
				$response->setContent($contents);
			}
		}
		$this->invokeHooks(self::HOOK_AFTER, $wrapperArguments);
		$this->optionalMethod($afterMethod, $wrapperArguments);
		$this->after($request, $response);
		return $response;
	}

	/**
	 * Stub for override - initialize the controller - called after __construct is done but before hook_initialize
	 * Note that:
	 * <code>
	 * $this->route
	 * </code>
	 * May all possibly be NULL upon this function called.
	 */
	protected function initialize(): void {
	}

	/**
	 * Controller call is called before every action
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	protected function before(Request $request, Response $response): void {
	}

	/**
	 * Controller call is called after every action
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	protected function after(Request $request, Response $response): Response {
		return $response;
	}

	/**
	 * Returns an array of name/value pairs for a template
	 */
	public function variables(): array {
		return [
			'application' => $this->application, 'controller' => $this,
		];
	}

	public const FILTER_JSON = self::class . 'json';

	/**
	 * Update all settings to return a JSON response
	 *
	 * @param Response $response
	 * @param mixed $mixed
	 * @return Response
	 */
	public function responseJSON(Response $response, mixed $mixed = null): Response {
		$mixed = $this->invokeTypedFilters(self::FILTER_JSON, $mixed, [$this]);
		$response->json()->setData($mixed);
		return $response;
	}

	/**
	 * Page not found error
	 *
	 * @param Response $response
	 * @param string $message
	 * @return Response
	 */
	public function error_404(Response $response, string $message = ''): Response {
		$this->error($response, HTTP::STATUS_FILE_NOT_FOUND, rtrim("Page not found $message"));
		return $response;
	}

	/**
	 * Generic page error
	 *
	 * @param Response $response
	 * @param int $code HTTP::Status_XXX
	 * @param string $message Message
	 * @return Response
	 */
	public function error(Response $response, int $code, string $message = ''): Response {
		$response->setStatus($code, $message);
		$response->setContent($message);
		return $response;
	}

	/**
	 * Execute an optional method
	 *
	 * @param array|string $names
	 * @param array $arguments
	 * @param mixed|null $default
	 * @return mixed
	 */
	final public function optionalMethod(array|string $names, array $arguments, mixed $default = null): mixed {
		foreach (Types::toList($names) as $name) {
			if ($this->hasMethod($name)) {
				return $this->invokeMethod($name, $arguments);
			}
		}
		return $default;
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function hasMethod(string $name): bool {
		return method_exists($this, $name);
	}

	/**
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	final public function invokeMethod(string $name, array $arguments): mixed {
		return call_user_func_array([
			$this, $name,
		], $arguments);
	}

	/**
	 *
	 * @param string $action
	 * @param mixed $object
	 * @param array $options
	 * @return array:
	 */
	public function getRouteMap(string $action = '', mixed $object = null, array $options = []): array {
		return [];
	}

	/**
	 * Create a model
	 *
	 * @return Model
	 */

	/**
	 * @param string $class Model to create
	 * @param mixed $mixed Model initialization parameter (id, array, etc.)
	 * @param array $options Creation options and initial options for model
	 * @return Model
	 * @throws ClassNotFound
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return $this->application->modelFactory($class, $mixed, $options);
	}

	/**
	 * Handles an action OPTIONS using introspection.
	 *
	 * @param Response $response
	 * @param string $action
	 * @return Response
	 */
	final public function handleOPTIONS(Response $response, string $action): Response {
		$map = ['action' => $action];
		$options = [HTTP::METHOD_OPTIONS => true];
		foreach (HTTP::$methods as $method) {
			$map['method'] = strtolower($method);
			$map['METHOD'] = strtoupper($method);
			foreach ($this->actionMethods as $actionMethod) {
				$method = ArrayTools::map($actionMethod, $map);
				if ($this->hasMethod($method)) {
					$options[$method] = true;
				}
			}
		}
		$response->setHeader('Allow', implode(',', array_keys($options)));
		$response->setContent('');
		$response->setStatus(HTTP::STATUS_NO_CONTENT);
		$response->raw();
		return $response;
	}

	/**
	 * Collect a list of all controllers found in this application. This loads all PHP
	 * within the autoloader paths of the application at time of first invocation.
	 *
	 * Possibly very slow.
	 *
	 * @param Application $application
	 * @return array
	 */
	final public static function all(Application $application): array {
		$paths = $application->autoloader->path();

		try {
			$item = $application->cacheItemPool()->getItem(__CLASS__);
		} catch (InvalidArgumentException) {
			$item = null;
		}
		if ($item && $item->isHit()) {
			$value = $item->get();
			if (count($paths) === $value['pathCount']) {
				return $value['all'];
			}
		}
		$list_options = [
			Directory::LIST_RULE_FILE => ['/\.(php)$/' => true, false],
			Directory::LIST_RULE_DIRECTORY_WALK => ['#/\.#' => false, true], Directory::LIST_RULE_DIRECTORY => false,
		];
		foreach ($paths as $path => $options) {
			try {
				$allIncludes = Directory::listRecursive($path, $list_options);
			} catch (ParameterException) {
				$allIncludes = []; // never
			}
			foreach ($allIncludes as $controllerInclude) {
				if (!StringTools::contains($controllerInclude, ['/Controller', 'Controller/'])) {
					continue;
				}
				$args = ['controllerInclude' => $controllerInclude];
				$application->debug('Found controller {controllerInclude}', $args);

				try {
					$application->load($controllerInclude);
				} catch (Throwable $e) {
					$args += Exception::exceptionVariables($e);
					$application->error('Exception creating controller {controller_inc} {throwableClass}', $args);
				}
			}
		}
		$application->classes->register(get_declared_classes());
		$controllers = $application->classes->register(Controller::class);
		foreach ($controllers as $controllerClass) {
			try {
				$reflectionClass = new ReflectionClass($controllerClass);
			} catch (ReflectionException) {
				continue;
			}
			if ($reflectionClass->isAbstract()) {
				continue;
			}
			$found[] = $controllerClass;
		}
		sort($found);
		$value = [];
		$value['all'] = $found;
		$value['pathCount'] = count($paths);
		if ($item) {
			$application->cacheItemPool()->saveDeferred($item->set($value));
		}
		return $found;
	}

	/**
	 * Output to a PHP constructor parameters
	 *
	 * @return string
	 */
	public function _to_php(): string {
		return '$application, ' . PHP::dump($this->options());
	}

	/**
	 * @param array $methods
	 * @param array $requestMap
	 * @param bool $require
	 * @return string
	 * @throws NotFoundException
	 */
	private function determineMethod(array $methods, array $requestMap, bool $require): string {
		foreach ($methods as $argumentMethod) {
			$argumentMethod = ArrayTools::map($argumentMethod, $requestMap);
			if ($this->hasMethod($argumentMethod)) {
				return $argumentMethod;
			}
		}
		if ($require) {
			throw new NotFoundException('{path} ({method}) not found', $requestMap);
		}
		return '';
	}
}
