<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

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
	 * @var Controller
	 */
	protected Controller $controller;

	/**
	 * The action to invoke
	 *
	 * @var string
	 */
	protected string $controller_action = '';

	/**
	 * @return void
	 * @throws Exception_Parameter
	 * @throws Exception_Class_NotFound
	 */
	protected function initialize(): void {
		$action = $this->option('action', '');
		if (!is_string($action)) {
			throw new Exception_Parameter('Action must be a string: {type}', [
				'type' => type($action),
			]);
		}
		$this->controller = $this->controller();
	}

	/**
	 * @return array|string[]
	 */
	public function __sleep() {
		return array_merge(['controller'], parent::__sleep());
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
		$this->controller_action = '';
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
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception_NotFound
	 */
	public function _execute(Request $request, Response $response): Response {
		return $this->controller->execute($request, $response, $this->_determineClassAction(), $this->args);
	}

	/**
	 * Create our controller
	 *
	 * @return Controller
	 * @throws Exception_Class_NotFound
	 */
	public function controller(): Controller {
		$reflectionClass = $this->_controllerReflection();

		try {
			return $reflectionClass->newInstance($this->application, $this, $this->optionArray('controller
		options') + $this->options());
		} catch (ReflectionException $e) {
			$class_name = $reflectionClass->getName();

			throw new Exception_Class_NotFound($class_name, 'Class {class_name} newInstance failed {message}, can not instantiate', [
				'class_name' => $class_name, 'message' => $e->getMessage(),
			], $e);
		}
	}

	/**
	 * Create our controller
	 *
	 * @return ReflectionClass
	 * @throws Exception_Class_NotFound
	 */
	private function _controllerReflection(): ReflectionClass {
		$class_name = $this->option('controller');

		try {
			$reflectionClass = new ReflectionClass($class_name);
			$this->log('Controller {class_name} created', [
				'class_name' => $class_name,
			]);
			if ($reflectionClass->isAbstract()) {
				throw new Exception_Class_NotFound($class_name, 'Class {class_name} is abstract, can not instantiate', [
					'class_name' => $class_name,
				]);
			}
		} catch (ReflectionException $e) {
			throw new Exception_Class_NotFound($class_name, map('Controller {controller} not found', [
				'controller' => $class_name,
			]), $e);
		}
		return $reflectionClass;
	}

	/**
	 * Determine the class of the controller and the action to run
	 *
	 * @return string
	 */
	private function _determineClassAction(): string {
		return $this->optionString('action', $this->optionString('default action'));
	}

	/**
	 *
	 * @return array
	 * @throws Exception_Class_NotFound
	 */
	public function classActions(): array {
		$actions = parent::classActions();
		$reflection = $this->_controllerReflection();
		$action_prefix = $this->actionMethodPrefix();
		$action_list = [];
		foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC) as $method) {
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
	 */
	protected function getRouteMap(string $action, Model $object = null, array $options = []): array {
		$map = parent::getRouteMap($action, $object, $options);
		$url = map($this->cleanPattern, $map);
		if (!$this->match($url)) {
			throw new Exception_Invalid('{method} {pattern} does not match {url} - route {original_pattern} is corrupt', [
				'method' => __METHOD__, 'url' => $url,
			] + $this->variables());
		}
		$this->_mapOptions();
		$controller = $this->controller();
		$map = $controller->getRouteMap($action, $object, $options) + $map;
		$this->_unmapOptions();
		return $map;
	}
}
