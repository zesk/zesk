<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use \ReflectionClass;

/**
 *
 * @author kent
 *
 */
class Route_Controller extends Route {
	/**
	 * Lazy evaluation of the reflection class to instantiate the controller
	 *
	 * @var ?ReflectionClass
	 */
	protected ?ReflectionClass $class = null;

	/**
	 *
	 * @var string The class which was instantiated.
	 */
	protected string $class_name = '';

	/**
	 * Lazy evaluation of the controller
	 *
	 * @var ?Controller
	 */
	protected ?Controller $controller = null;

	/**
	 * The action to invoke
	 *
	 * @var string
	 */
	protected string $controller_action = '';

	/**
	 * @return void
	 * @throws Exception_Parameter
	 */
	protected function initialize(): void {
		$action = $this->option('action', '');
		if (!is_string($action)) {
			throw new Exception_Parameter('Action must be a string: {type}', [
				'type' => type($action),
			]);
		}
	}

	/**
	 * @return array|string[]
	 */
	public function __sleep() {
		return array_merge([], parent::__sleep());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::__wakeup()
	 */
	public function __wakeup(): void {
		parent::__wakeup();
		$this->class = null;
		$this->class_name = '';
		$this->controller = null;
		$this->controller_action = '';
	}

	/**
	 *
	 * @param Response|null $response
	 * @throws Exception_Class_NotFound
	 */
	private function _initController(Response $response = null): void {
		if ($this->controller instanceof Controller) {
			return;
		}

		[$class, $this->controller_action] = $this->_determineClassAction();

		/* @var $controller Controller */
		$this->controller = $class->newInstance($this->application, $this, $response, $this->optionArray('controller options') + $this->options);
	}

	/**
	 *
	 * @return string
	 */
	public function actionMethodPrefix(): string {
		return strval($this->option('method prefix', 'action_'));
	}

	/**
	 * Execute this route
	 *
	 * @param Response $response
	 * @return Response
	 * @throws Exception_System
	 * @throws Exception_Redirect
	 */
	public function _execute(Response $response): Response {
		$app = $this->application;
		$this->_initController($response);
		$controller = $this->controller;
		assert($controller instanceof Controller);
		$action = $this->controller_action;
		assert($action !== '');

		$__ = [
			'class' => $controller::class, 'action' => $action,
		];
		$app->logger->debug('Controller {class} running action {action}', $__);
		$actionMethod = str_replace('-', '_', $action);

		try {
			$controller->optionalMethod([
				'before_' . $actionMethod, 'before',
			], []);
			$controller->callHook('before');

			$argumentsMethod = $this->option('arguments method', $this->option('arguments method prefix', 'arguments_') . $actionMethod);
			$method = $this->option('method', $this->actionMethodPrefix() . $actionMethod);
			$method = map($method, [
				'method' => $this->request->method(),
			]);

			if ($response->status_code === HTTP::STATUS_OK) {
				ob_start();
				if ($controller->hasMethod($method)) {
					$result = $controller->optionalMethod($argumentsMethod, $this->args);
					$args = is_array($result) ? $result : $this->args;
					$result = $controller->invokeMethod($method, $args);
				} else {
					if ($action !== 'index') {
						$app->logger->warning('No such method {method} in {class}', [
							'method' => $method, 'class' => $controller::class,
						]);
					}
					$result = $controller->invokeDefaultMethod(array_merge([
						$action,
					], $this->args));
				}
				$contents = ob_get_clean();
				$controller->optionalMethod([
					'after_' . $actionMethod, 'after',
				], [
					$result, $contents,
				]);
				$controller->callHook('after');
			}
		} catch (Exception_Redirect $e) {
			throw $e;
		} catch (\Exception $e) {
			$app->hooks->call('exception', $e);
			$controller->optionalMethod([
				'exception_' . $actionMethod, 'exception',
			], [
				$e,
			]);

			throw new Exception_System('Unhandled exception {class}', Exception::exceptionVariables($e), 0, $e);
		}
		return $response;
	}

	/**
	 * Determine the class of the controller and the action to run
	 *
	 * @return array
	 * @throws Exception_Class_NotFound
	 */
	private function _determineClassAction(): array {
		$class_name = $this->option('controller');
		$options = ($this->named ?? []) + $this->options;

		$this->class = $reflectionClass = null;

		try {
			$this->class = $reflectionClass = new ReflectionClass($class_name);
			$this->class_name = $class_name;
			$this->log('Controller {class_name} created', [
				'class_name' => $class_name,
			]);
		} catch (\ReflectionException $e) {
		}
		if (!$reflectionClass) {
			throw new Exception_Class_NotFound($class_name, map('Controller {controller} not found', [
				'controller' => $class_name,
			]));
		}
		if ($reflectionClass->isAbstract()) {
			throw new Exception_Class_NotFound('Class {class_name} is abstract, can not instantiate', [
				'class_name' => $class_name,
			]);
		}
		$action = $options['action'] ?? $this->option('default action', 'index');
		return [
			$reflectionClass, $action,
		];
	}

	/**
	 *
	 * @return array
	 * @throws Exception_Class_NotFound
	 */
	public function classActions(): array {
		$actions = parent::classActions();
		[$reflection] = $this->_determineClassAction();
		$action_prefix = $this->actionMethodPrefix();
		$action_list = [];
		/* @var $reflection \ReflectionClass */
		foreach ($reflection->getMethods(\ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PUBLIC) as $method) {
			$name = $method->getName();
			if (str_starts_with($name, $action_prefix)) {
				$action_list[] = StringTools::removePrefix($name, $action_prefix);
			}
		}
		$actions[strtolower($reflection->getName())] = $action_list;
		return $actions;
	}

	/**
	 * @param string $action
	 * @param Model|null $object
	 * @param array $options
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Invalid
	 * @throws Exception_NotFound
	 */
	protected function getRouteMap(string $action, Model $object = null, array $options = []): array {
		$map = parent::getRouteMap($action, $object, $options);
		$url = map($this->clean_pattern, $map);
		if (!$this->match($url)) {
			throw new Exception_Invalid('{method} {pattern} does not match {url} - route {original_pattern} is corrupt', [
				'method' => __METHOD__, 'url' => $url,
			] + $this->variables());
		}
		$this->_mapOptions();
		$this->_initController();
		$map = $this->controller->getRouteMap($action, $object, $options) + $map;
		$this->_unmapOptions();
		return $map;
	}
}
