<?php
declare(strict_types=1);

namespace zesk;

use Closure;
use ReflectionMethod;
use Throwable;

/**
 *
 * @author kent
 *
 */
class Route_Method extends Route {
	/**
	 * Method to call
	 */
	public const OPTION_METHOD = 'method';

	/**
	 * Whether to load the class using the autoloader or whether it should already be loaded.
	 */
	public const OPTION_AUTOLOAD = 'autoload';

	public const OPTION_INCLUDE = 'include';

	public const OPTION_REQUIRE = 'require';

	public const OPTION_EMPTY_CONTENT = 'emptyContent';

	/**
	 * Value is bool
	 *
	 * Always use the buffer as content.
	 *
	 * @var string
	 */
	public const OPTION_BUFFER = 'buffer';

	/**
	 * Value is bool
	 *
	 * Never use buffer as content. Beats `buffer`.
	 *
	 * @var string
	 */
	public const OPTION_NO_BUFFER = 'noBuffer';

	/**
	 * Value is string
	 *
	 * Method returned as an array is joined using this prefix and suffix combo
	 *
	 * @var string
	 */
	public const OPTION_JOIN_PREFIX = 'joinPrefix';

	/**
	 * Value is string
	 *
	 * Method returned as an array is joined using this prefix and suffix combo
	 *
	 * @var string
	 */
	public const OPTION_JOIN_SUFFIX = 'joinSuffix';

	public const OPTION_PREFIX = 'prefix';

	public const OPTION_SUFFIX = 'suffix';

	/**
	 * @return bool
	 * @throws Exception_Parameter|Exception_File_NotFound
	 */
	public function validate(): bool {
		$function = $this->option(self::OPTION_METHOD);
		return $this->validateMethod($function);
	}

	/**
	 * @param array|string|callable|Closure $function
	 * @return bool
	 * @throws Exception_File_NotFound
	 * @throws Exception_Parameter
	 */
	private function validateMethod(array|string|callable|Closure $function): bool {
		$class = $method = '';
		if (is_string($function)) {
			[$class, $method] = pair($function, '::', '', $function);
		} elseif (is_array($function)) {
			[$class, $method] = $function;
		}
		[$include, $require] = $this->_includeFiles();
		if (is_string($class)) {
			if (!class_exists($class, $this->optionBool(self::OPTION_AUTOLOAD, true))) {
				throw new Exception_Parameter('No such class found {class}', [
					'class' => $class,
				]);
			}
			if (!method_exists($class, $method)) {
				throw new Exception_Parameter("No such method {class}::{method} exists in $require or $include for {pattern}", $this->variables() + [
					'require' => $require, 'include' => $include, 'method' => $method,
				]);
			}
		} elseif (is_object($class)) {
			if (!method_exists($class, $method)) {
				throw new Exception_Parameter("No such method {objectClass}::{method} exists in $require or $include for {pattern}", $this->variables() + [
					'require' => $require, 'include' => $include, 'method' => $method,
					'objectClass' => $class::class,
				]);
			}
		} elseif (is_string($function)) {
			if (!function_exists($function)) {
				throw new Exception_Parameter('No such function exists in {require} or {include} for {pattern}', $this->variables() + [
					'require' => $require, 'include' => $include,
				]);
			}
		} elseif (!is_callable($function)) {
			throw new Exception_Parameter('Not callable: {callable} for {pattern}', $this->variables() + [
				'callable' => Hooks::callable_string($function), 'pattern' => $this->pattern,
			]);
		}
		return true;
	}

	/**
	 * Perform includes if specified
	 *
	 * @return array
	 * @throws Exception_File_NotFound
	 */
	private function _includeFiles(): array {
		$includes = $this->optionIterable(self::OPTION_INCLUDE);
		$requires = $this->optionIterable(self::OPTION_REQUIRE);
		foreach ($requires as $require) {
			File::depends($require);

			try {
				require_once($require);
			} catch (Throwable $t) {
				throw new Exception_File_NotFound($require, 'Loading route {pattern} require: {require}', [
					'require' => $require,
				] + $this->variables(), 0, $t);
			}
		}
		foreach ($includes as $include) {
			try {
				$this->application->load($include);
			} catch (Throwable $t) {
				throw new Exception_File_NotFound($include, '{throwableClass} {message} Loading route {pattern} include: {include}', [
					'include' => $include,
				] + Exception::exceptionVariables($t) + $this->variables(), 0, $t);
			}
		}
		return [$includes, $requires];
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param string|callable|Closure $method
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception_Redirect
	 */
	private function executeMethod(Request $request, Response $response, string|callable|Closure $method, array $arguments): mixed {
		$app = $this->application;

		try {
			if (is_string($method) && str_contains($method, '::')) {
				[$class, $method] = pair($method, '::', 'stdClass', $method);
				$method = new ReflectionMethod($class, $method);
				$construct_arguments = $this->_mapVariables($request, $response, $this->optionArray('construct arguments'));
				$object = $method->isStatic() ? null : $app->objects->factoryArguments($class, $construct_arguments);
				/** @throws Exception_Redirect */
				$content = $method->invokeArgs($object, $arguments);
			} else {
				$content = call_user_func_array($method, $arguments);
			}
		} catch (Exception_Redirect $e) {
			throw $e;
		} catch (Throwable $e) {
			$content = null;
			$app->hooks->call('exception', $e);
			$app->logger->error('{class}::_execute() Running {method} threw exception {e}', [
				'class' => __CLASS__, 'method' => $app->hooks->callable_string($method), 'e' => $e,
			]);
		}
		return $content;
	}

	public const OPTION_BEFORE_METHODS = 'beforeMethods';

	public const OPTION_BEFORE_METHOD_STATUS = 'beforeMethodStatus';

	public const OPTION_BEFORE_METHOD_CONTENT = 'beforeMethodContent';

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Exception_File_NotFound
	 * @throws Exception_Redirect
	 */
	protected function _execute(Request $request, Response $response): Response {
		$response->setContent();

		$this->_includeFiles();
		foreach ($this->optionIterable(self::OPTION_BEFORE_METHODS) as $beforeMethod) {
			$beforeMethod = $this->_mapVariables($request, $response, $beforeMethod);
			if ($this->validateMethod($beforeMethod)) {
				$result = $this->executeMethod($request, $response, $beforeMethod, [$request, $response]);
				if (!$result) {
					$response->setStatus($this->optionInt(self::OPTION_BEFORE_METHOD_STATUS, HTTP::STATUS_UNAUTHORIZED));
					$response->setContent($this->optionString(self::OPTION_BEFORE_METHOD_CONTENT, 'Not allowed'));
					return $response;
				}
			}
		}

		try {
			$method = $this->_mapVariables($request, $response, $this->options[self::OPTION_METHOD]);
			ob_start();
			$content = $this->executeMethod($request, $response, $method, $this->_mapVariables($request, $response, $this->args));
			$buffer = ob_get_clean();
		} catch (Throwable $t) {
			ob_get_clean();

			throw $t;
		}
		if ($content instanceof Response) {
			$response = $content;
			$content = null;
		}
		/* Content was set, just return */
		if ($response->content() !== null) {
			return $response;
		}
		if (!$this->optionBool(self::OPTION_NO_BUFFER)) {
			if ($content === null && !empty($buffer)) {
				$content = $buffer;
			} elseif ($this->optionBool(self::OPTION_BUFFER)) {
				$content = $buffer;
			}
		}
		if (empty($content)) {
			$content = $this->option(self::OPTION_EMPTY_CONTENT, '');
		}
		if ($content !== null && $this->optionString('')) {
			if (is_array($content)) {
				$content = ArrayTools::joinWrap($content, $this->option(self::OPTION_JOIN_PREFIX, ''), $this->option(self::OPTION_JOIN_SUFFIX, ''));
			}
			$content = $this->option(self::OPTION_PREFIX, '') . $content . $this->option(self::OPTION_SUFFIX, '');
		}
		if ($response->isJSON()) {
			if ($content !== null) {
				$response->json()->setData($content);
			}
			return $response;
		}
		return $response;
	}
}
