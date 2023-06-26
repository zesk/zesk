<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Route;

use zesk\ArrayTools;
use zesk\Route;
use zesk\Controller as ControllerBase;
use zesk\Request;
use zesk\Response;
use zesk\StringTools;
use zesk\Model;
use zesk\Exception\NotFoundException;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use zesk\Exception\ClassNotFound;

/**
 *
 * @author kent
 *
 */
class Controller extends Route {
	/**
	 *
	 */
	public const OPTION_ACTION = 'action';

	/**
	 *
	 */
	public const DEFAULT_ACTION = 'index';

	/**
	 * Lazy evaluation of the reflection class to instantiate the controller
	 *
	 * @var ?ReflectionClass
	 */
	protected ?ReflectionClass $class = null;

	/**
	 * Lazy evaluation of the controller
	 *
	 * @var ControllerBase
	 */
	protected ControllerBase $controller;

	/**
	 * @return void
	 * @throws ClassNotFound
	 */
	protected function initialize(): void {
		$this->controller = $this->controller();
	}

	/**
	 * @return array|string[]
	 */
	public function __serialize(): array {
		return parent::__serialize();
	}

	/**
	 *
	 * @see Route::__unserialize()
	 */
	public function __unserialize(array $data): void {
		parent::__unserialize($data);
		$this->initialize();
	}

	/**
	 *
	 * @return string
	 */
	public function actionMethodPrefix(): string {
		return strval($this->option('method prefix', 'action_'));
	}

	/**
	 * Returns our action and the arguments (default) for the controller.
	 *
	 * Controller can do what it wants with these.
	 *
	 * @param Request $request
	 * @return array
	 */
	public function determineAction(Request $request): array {
		$action = $this->optionString(self::OPTION_ACTION, self::DEFAULT_ACTION);
		if (StringTools::hasTokens($action)) {
			$action = $this->_mapVariables($request, $action);
		}
		return [$action, $this->_mapVariables($request, $this->args)];
	}

	/**
	 * Execute this route
	 *
	 * @param Request $request
	 * @return Response
	 * @throws NotFoundException
	 * @see Route::internalExecute()
	 */
	public function internalExecute(Request $request): Response {
		$response = $this->application->responseFactory($request);
		[$action, $actionArguments] = $this->determineAction($request);
		return $this->controller->execute($request, $response, $action, $actionArguments);
	}

	/**
	 * Create our controller
	 *
	 * @return ControllerBase
	 * @throws ClassNotFound
	 */
	public function controller(): ControllerBase {
		$reflectionClass = $this->_controllerReflection();

		try {
			return $reflectionClass->newInstance($this->application, $this, $this->optionArray('controller
		options') + $this->options());
		} catch (ReflectionException $e) {
			$class_name = $reflectionClass->getName();

			throw new ClassNotFound($class_name, 'Class {class_name} newInstance failed {message}, can not instantiate', [
				'class_name' => $class_name, 'message' => $e->getMessage(),
			], $e);
		}
	}

	/**
	 * Create our controller
	 *
	 * @return ReflectionClass
	 * @throws ClassNotFound
	 */
	private function _controllerReflection(): ReflectionClass {
		$className = $this->optionString('controller');

		try {
			$reflectionClass = new ReflectionClass($className);
			$this->log('Controller {class_name} created', [
				'class_name' => $className,
			]);
			if ($reflectionClass->isAbstract()) {
				throw new ClassNotFound($className, 'Class {class_name} is abstract, can not instantiate', [
					'class_name' => $className,
				]);
			}
		} catch (ReflectionException $e) {
			throw new ClassNotFound($className, 'Controller {controller} not found', [
				'controller' => $className,
			], $e);
		}
		return $reflectionClass;
	}

	/**
	 *
	 * @return array
	 * @throws ClassNotFound
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
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 */
	protected function getRouteMap(string $action, Model $object = null, array $options = []): array {
		$map = parent::getRouteMap($action, $object, $options);
		$url = ArrayTools::map($this->cleanPattern, $map);
		if (!$this->match($url)) {
			throw new NotFoundException('{method} {pattern} does not match {url} - route {original_pattern} is corrupt', [
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
