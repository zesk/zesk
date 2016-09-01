<?php
class Route_Method extends Route {
	public function validate(Application $application) {
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
			global $zesk;
			/* @var $zesk zesk\Kernel */
			throw new Exception_Parameter("Not callable: {callable} for {pattern}", array(
				"callable" => $zesk->hooks->callable_string($function),
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
	 * Use parent processing - honors objects {request} {response} etc.
	 * - this doesn't
	 *
	 * @todo
	 *
	 *
	 *
	 * @deprecated
	 *
	 *
	 *
	 * @param unknown $name        	
	 * @param unknown $default        	
	 * @return Ambigous <unknown, multitype:mixed , mixed, string, multitype:, Hookable, array,
	 *         number>
	 */
	private function option_arguments($name, $default = array()) {
		$arguments = $this->option($name);
		if (is_array($arguments)) {
			$arguments = map($arguments, $this->args);
		} else if (is_string($arguments)) {
			$arguments = array(
				map($arguments, $this->args)
			);
		} else {
			$arguments = $default;
		}
		return $arguments;
	}
	protected function _execute(Application $app) {
		$this->_do_includues();
		
		$method = $this->options['method'];
		$arguments = $this->args;
		
		$construct_arguments = $this->option_arguments("construct arguments", array());
		ob_start();
		try {
			if (is_string($method) && strpos($method, "::") !== false) {
				list($class, $method) = pair($method, '::', 'stdClass', $method);
				$method = new ReflectionMethod($class, $method);
				$object = $method->isStatic() ? null : zesk::factory_array($class, $construct_arguments);
				$content = $method->invokeArgs($object, $arguments);
			} else {
				$content = call_user_func_array($method, $arguments);
			}
		} catch (Exception $e) {
			global $zesk;
			$content = null;
			$zesk->hooks->call("exception", $e);
			log::error("{class}::_execute() Running {method} threw exception {e}", array(
				"class" => __CLASS__,
				"method" => zesk::callable_string($method),
				"e" => $e
			));
		}
		$buffer = ob_get_clean();
		if ($app->response->content !== null) {
			return;
		}
		if ($this->option_bool('json') || $this->option("content_type") === Response_HTML::content_type_json || $app->response->json()) {
			if ($content !== null) {
				$app->response->json($content);
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
				$content = arr::join_wrap($content, $this->option("join_prefix", ""), $this->option("join_suffix", ""));
			}
			$app->response->content = $this->option("prefix", "") . $content . $this->option("suffix", "");
		}
	}
}
