<?php

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
	 *
	 * @var ReflectionClass
	 */
	protected $class = null;

	/**
	 *
	 * @var string The class which was instantiated.
	 */
	protected $class_name = null;

	/**
	 *
	 * @var Controller
	 */
	protected $controller = null;
	/**
	 *
	 * @var string
	 */
	protected $controller_action = null;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::__sleep()
	 */
	public function __sleep() {
		return array_merge(array(), parent::__sleep());
	}
	public function initialize() {
		// To allow modules to set defaults in child controllers.
		$this->inherit_global_options();
		foreach (to_list("controller prefix;controller prefixes") as $option) {
			if ($this->has_option($option)) {
				$this->router->application->zesk->deprecated(map("Option {option} in route {name} is deprecated 2017-02", array(
					"option" => $option,
					"name" => $this->clean_pattern
				)));
			}
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::__wakeup()
	 */
	public function __wakeup() {
		$this->class = null;
		$this->class_name = null;
		$this->controller = null;
		$this->controller_action = null;
	}

	/**
	 *
	 * @return multitype:Controller string
	 */
	private function _init_controller() {
		if ($this->controller instanceof Controller) {
			return array(
				$this->controller,
				$this->controller_action
			);
		}

		list($class, $this->controller_action) = $this->_determine_class_action();

		/* @var $controller Controller */
		$this->controller = $class->newInstance($this->router->application, $this->option_array("controller options") + $this->options);

		return array(
			$this->controller,
			$this->controller_action
		);
	}

	/**
	 * Execute this route
	 *
	 * @see Route::_execute()
	 */
	function _execute() {
		$app = $this->router->application;
		list($controller, $action) = $this->_init_controller();
		$__ = array(
			'class' => get_class($controller),
			'action' => $action
		);
		$app->logger->debug("Controller {class} running action {action}", $__);
		$action_method = str_replace("-", "_", $action);
		try {
			$controller->optional_method(array(
				'before_' . $action_method,
				"before"
			), array());

			$arguments_method = $this->option('arguments method', $this->option('arguments method prefix', 'arguments_') . $action_method);
			$method = $this->option('method', $this->option('method prefix', 'action_') . $action_method);

			$try_default = false;
			ob_start();
			if ($controller->has_method($method)) {
				$args = $controller->optional_method($arguments_method, $this->args);
				$result = $controller->invoke_method($method, $args);
			} else {
				if ($action !== "index") {
					$app->logger->warning("No such method {method} in {class}", array(
						"method" => $method,
						"class" => get_class($controller)
					));
				}
				$result = $controller->invoke_default_method(array_merge(array(
					$action
				), $this->args));
			}
			$contents = ob_get_clean();
			$controller->optional_method(array(
				'after_' . $action_method,
				"after"
			), array(
				$result,
				$contents
			));
		} catch (Exception $e) {
			$app->hooks->call("exception", $e);
			$controller->optional_method(array(
				'exception_' . $action_method,
				"exception"
			), array(
				$e
			));
			throw $e;
		}
	}

	/**
	 * Determine the class of the controller and the action to run
	 *
	 * @throws Exception_Class_NotFound
	 * @return array
	 */
	private function _determine_class_action() {
		$controller = $this->option("controller");
		if (strpos($controller, "\\") !== false || strpos($controller, "Controller_") !== false) {
			$try_classes = array(
				$controller
			);
			$options = $this->named + $this->options;
		} else {
			/**
			 *
			 * @deprecated 2017-02
			 */
			if ($this->has_option('controller prefix')) {
				$prefixes = array(
					$this->option('controller prefix')
				);
			} else {
				$prefixes = $this->option_list('controller prefixes', array(
					"Controller_"
				));
			}
			$options = $this->named + $this->options;
			$default_controller = avalue($options, "default controller", "index");
			$controller = ucfirst(aevalue($options, "controller", $default_controller));
			$try_classes = array();
			$default_classes = array();
			foreach ($prefixes as $prefix) {
				$try_classes[] = begins($controller, $prefix) ? $controller : $prefix . $controller;
				$default_classes[] = $prefix . $default_controller;
			}
			$try_classes = array_unique(array_merge($try_classes, $default_classes));
		}

		$this->class = $reflectionClass = null;
		foreach ($try_classes as $class_name) {
			try {
				$this->class = $reflectionClass = new ReflectionClass($class_name);
				$this->class_name = $class_name;
				$this->log("Controller $class_name created");
				break;
			} catch (\ReflectionException $e) {
			} catch (Exception_Class_NotFound $e) {
			}
		}
		if (!$reflectionClass) {
			$classes = implode(", ", $try_classes);
			throw new Exception_Class_NotFound($classes, __("Controller {controller} not found: {classes}", array(
				'controller' => $controller,
				'classes' => $classes
			)));
		}
		if ($reflectionClass->isAbstract()) {
			throw new Exception_Class_NotFound('Class {class_name} is abstract, can not instantiate', array(
				'class_name' => $class_name
			));
		}
		$action = aevalue($options, "action", $this->option("default action", "index"));

		return array(
			$reflectionClass,
			$action
		);
	}

	/**
	 *
	 * @param unknown $action
	 * @param unknown $object
	 * @param unknown $options
	 * @return number
	 */
	protected function get_route_map($action, $object = null, $options = null) {
		$map = parent::get_route_map($action, $object, $options);
		$url = map($this->clean_pattern, $map);
		if (!$this->match($url)) {
			die("pattern didn't match");
		}
		$this->_map_options();
		list($controller) = $this->_init_controller();
		if ($controller) {
			$map = $controller->get_route_map($action, $object, $options) + $map;
		}
		$this->_unmap_options();
		return $map;
	}

	/**
	 *
	 * @return stdClass[]
	 */
	protected function hook_controllers() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$controller = $this->option('controller');
		if (map_clean($controller) !== $controller) {
			return array();
		}
		return array(
			$controller => $zesk->objects->factory("Controller_" . $controller, $this->router->application)
		);
	}
}
