<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use stdClass;
use Throwable;

/**
 *
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 *            Created on Fri Apr 02 21:04:09 EDT 2010 21:04:09
 */
class Controller extends Hookable implements Interface_Theme {
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
		$this->callHook('initialize');
	}

	/**
	 * Shortcut for subclass methods
	 *
	 * @param array|string $types
	 * @param array $arguments
	 * @param array $options
	 * @return string
	 * @throws Exception_Redirect
	 */
	public function theme(array|string $types, array $arguments = [], array $options = []): string {
		return $this->application->theme($types, $arguments, $options);
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

	/**
	 * Execute this route
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param string $action
	 * @param array $arguments
	 * @return Response
	 * @throws Exception_NotFound
	 */
	final public function execute(Request $request, Response $response, string $action = '', array $arguments = []): Response {
		$wrapperArguments = [$request, $response, $arguments, $action];
		$app = $this->application;
		$requestMap = [
			'method' => strtolower($request->method()),
			'action' => $action ?: 'index',
			'METHOD' => strtoupper($request->method()),
		];
		$beforeMethod = $this->determineMethod($this->beforeMethods, $requestMap, false);
		$argumentsMethod = $this->determineMethod($this->argumentMethods, $requestMap, false);
		$actionMethod = $this->determineMethod($this->actionMethods, $requestMap, true);
		$afterMethod = $this->determineMethod($this->afterMethods, $requestMap, false);
		$__ = $requestMap + [
			'class' => $this::class, 'actionMethod' => $actionMethod,
		];
		$app->logger->debug('Controller {class} running action {method} {action} -> {actionMethod}', $__);

		$this->optionalMethod($beforeMethod, $wrapperArguments);
		$this->callHookArguments('before', $wrapperArguments);
		if ($response->status_code !== HTTP::STATUS_OK) {
			return $response;
		}
		$arguments = $this->optionalMethod($argumentsMethod, $wrapperArguments, $arguments);
		if (!is_array($arguments)) {
			throw new Exception_NotFound("$argumentsMethod did not return an array", [
				'argumentsMethod' => $argumentsMethod,
			]);
		}
		ob_start();
		$result = $this->invokeMethod($actionMethod, $arguments);
		$contents = ob_get_clean();
		if ($result instanceof Response) {
			$response = $result;
		}
		if (strlen($contents)) {
			if (!$this->optionBool('captureOutput')) {
				$this->application->logger->warning('Controller {actionMethod} output {bytes} bytes: {contents}', [
					'actionMethod' => $actionMethod, 'bytes' => strlen($contents), 'contents' => $contents,
				]);
			}
			if (is_string($result)) {
				$contents .= $result;
			}
			$response->setContent($contents);
		}
		if (is_string($result)) {
			$response->setContent($result);
		}
		$this->callHookArguments('after', $wrapperArguments);
		$this->optionalMethod($afterMethod, $wrapperArguments);
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
	 * @param Request $request
	 * @param Response $response
	 * @param string $action
	 * @return mixed
	 */
	public function _actionDefault(Request $request, Response $response, array $arguments, string $action): Response {
		return $this->error_404($response, $action ? "Action $action" : 'default action');
	}

	/**
	 * Returns an array of name/value pairs for a template
	 */
	public function variables(): array {
		return [
			'application' => $this->application, 'controller' => $this,
		];
	}

	/**
	 * Update all settings to return a JSON response
	 *
	 * @param Response $response
	 * @param mixed $mixed
	 * @return Response
	 */
	public function responseJSON(Response $response, mixed $mixed = null): Response {
		$mixed = $this->callHookArguments('json', [
			$mixed,
		], $mixed);
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
	 * @return mixed
	 */
	final public function optionalMethod(array|string $names, array $arguments, mixed $default = null): mixed {
		foreach (toList($names) as $name) {
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
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return $this->application->modelFactory($class, $mixed, $options);
	}

	/**
	 * Collect a list of all controllers found in this application. This loads all PHP
	 * within the autoloader paths of the application at time of first invocation.
	 *
	 * Possibly very slow.
	 *
	 * @param Application $application
	 * @return array
	 *
	 */
	final public static function all(Application $application): array {
		$paths = $application->autoloader->path();

		try {
			$item = $application->cache->getItem(__CLASS__);
		} catch (InvalidArgumentException) {
			$item = null;
		}
		if ($item && $item->isHit()) {
			$value = $item->get();
			if (count($paths) === $value->n_paths) {
				return $value->all;
			}
		}
		$list_options = [
			Directory::LIST_RULE_FILE => ['/\.(php)$/' => true, false],
			Directory::LIST_RULE_DIRECTORY_WALK => ['#/\.#' => false, true], Directory::LIST_RULE_DIRECTORY => false,
		];
		$found = [];
		foreach ($paths as $path => $options) {
			$controller_path = path($path, 'controller');
			if (is_dir($controller_path)) {
				$classPrefix = $options['classPrefix'] ?? '';
				$controller_incs = Directory::list_recursive($controller_path, $list_options);
				foreach ($controller_incs as $controller_inc) {
					if (str_contains("/$controller_inc", '/.')) {
						continue;
					}
					$application->logger->debug('Found controller {controller_inc}', compact('controller_inc'));

					try {
						$controller_inc = File::setExtension($controller_inc, '');
						$class_name = $classPrefix . 'Controller_' . strtr($controller_inc, '/', '_');
						$application->logger->debug('class name is {class_name}', compact('class_name'));
						$reflectionClass = new ReflectionClass($class_name);
						if (!$reflectionClass->isAbstract()) {
							/* @var $controller Controller */
							$controller = $reflectionClass->newInstance($application);
							$found[$reflectionClass->getName()] = [
								'path' => path($controller_path, $controller_inc),
								'classes' => $controller->callHook('classes', [], []),
							];
						}
					} catch (Throwable $e) {
						$application->logger->error('Exception creating controller {controller_inc} {e}', compact('controller_inc', 'e'));
					}
				}
			}
		}
		ksort($found);
		$value = new stdClass();
		$value->all = $found;
		$value->n_paths = count($paths);
		if ($item) {
			$application->cache->saveDeferred($item->set($value));
		}
		return $found;
	}

	/**
	 * Output to a PHP constructor parameters
	 *
	 * @return string
	 */
	public function _to_php(): string {
		return '$application, ' . PHP::dump($this->options);
	}

	/**
	 * @param array $methods
	 * @param array $requestMap
	 * @param bool $require
	 * @return string
	 * @throws Exception_NotFound
	 */
	private function determineMethod(array $methods, array $requestMap, bool $require): string {
		foreach ($methods as $argumentMethod) {
			$argumentMethod = map($argumentMethod, $requestMap);
			if ($this->hasMethod($argumentMethod)) {
				return $argumentMethod;
			}
		}
		if ($require) {
			throw new Exception_NotFound('{path} ({method}) not found', $requestMap);
		}
		return '';
	}
}
