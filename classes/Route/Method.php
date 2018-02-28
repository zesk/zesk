<?php
namespace zesk;

use ReflectionMethod;

/**
 *
 * @author kent
 *
 */
class Route_Method extends Route {
	public function validate() {
		$application = $this->router->application;
		$function = $this->options['method'];
		$class = $method = null;
		if (is_string($function)) {
			list($class, $method) = pair($function, "::", null, $function);
		}
		list($include, $require) = $this->_do_includues();
		if ($class) {
			if (!class_exists($class, $this->option_bool('autoload', true))) {
				throw new Exception_Parameter("No such class found {class}", array(
					"class" => $class
				));
			}
			if (!method_exists($class, $method)) {
				throw new Exception_Parameter("No such method {class}::{method} exists in $require or $include for {pattern}", array(
					'class' => $class,
					'require' => $require,
					'include' => $include,
					'pattern' => $this->pattern,
					'method' => $method
				));
			}
		} else if (is_string($function)) {
			if (!function_exists($function)) {
				throw new Exception_Parameter("No such function exists in {require} or {include} for {pattern}", array(
					"require" => $require,
					"include" => $include,
					"pattern" => $this->pattern
				));
			}
		} else if (!is_callable($function)) {
			throw new Exception_Parameter("Not callable: {callable} for {pattern}", array(
				"callable" => Hooks::callable_string($function),
				"pattern" => $this->pattern
			));
		}
	}

	/**
	 * Do includes if specified
	 *
	 * @return mixed[]|array[]
	 */
	private function _do_includues() {
		$include = avalue($this->options, 'include');
		$require = avalue($this->options, 'require');
		if ($require) {
			require_once $require;
		} else if ($include) {
			include_once $include;
		}
		return array(
			$include,
			$require
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Route::_execute()
	 */
	protected function _execute(Response $response) {
		$app = $this->router->application;
		$this->_do_includues();

		$method = $this->options['method'];
		$arguments = $this->args;

		$construct_arguments = $this->_map_variables($this->option_array("construct arguments"));
		$method = $this->_map_variables($method);
		ob_start();
		try {
			if (is_string($method) && strpos($method, "::") !== false) {
				list($class, $method) = pair($method, '::', 'stdClass', $method);
				$method = new ReflectionMethod($class, $method);
				$object = $method->isStatic() ? null : $app->objects->factory_arguments($class, $construct_arguments);
				$content = $method->invokeArgs($object, $arguments);
			} else {
				$content = call_user_func_array($method, $arguments);
			}
		} catch (Exception_Redirect $e) {
			throw $e;
		} catch (\Exception $e) {
			$content = null;
			$app->hooks->call("exception", $e);
			$app->logger->error("{class}::_execute() Running {method} threw exception {e}", array(
				"class" => __CLASS__,
				"method" => $app->hooks->callable_string($method),
				"e" => $e
			));
		}
		if ($content instanceof Response) {
			return $content;
		}
		$buffer = ob_get_clean();
		if ($response->content !== null) {
			return;
		}
		if ($response->is_json()) {
			if ($content !== null) {
				$response->json()->data($content);
			}
			return;
		}
		if (!$this->option_bool('no-buffer')) {
			if ($content === null && !empty($buffer)) {
				$content = $buffer;
			} else if ($this->option_bool('buffer')) {
				$content = $buffer;
			}
		}
		if (empty($content)) {
			$content = $this->option("empty content", "");
		}
		if ($content !== null) {
			if (is_array($content)) {
				$content = ArrayTools::join_wrap($content, $this->option("join_prefix", ""), $this->option("join_suffix", ""));
			}
			$response->content = $this->option("prefix", "") . $content . $this->option("suffix", "");
		}
	}
}
