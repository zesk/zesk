<?php
declare(strict_types=1);

namespace zesk;

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

	/**
	 * @return bool
	 * @throws Exception_Parameter
	 */
	public function validate(): bool {
		$function = $this->option(self::OPTION_METHOD);
		$class = $method = null;
		if (is_string($function)) {
			[$class, $method] = pair($function, '::', '', $function);
		}
		[$include, $require] = $this->_includeFiles();
		if ($class) {
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

	public const OPTION_INCLUDE = 'include';

	public const OPTION_REQUIRE = 'require';

	/**
	 * Do includes if specified
	 *
	 * @return void
	 * @throws Exception_File_NotFound
	 */
	private function _includeFiles(): void {
		$includes = $this->optionList(self::OPTION_INCLUDE);
		$requires = $this->optionList(self::OPTION_REQUIRE);
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
				throw new Exception_File_NotFound($require, 'Loading route {pattern} include: {include}', [
					'include' => $include,
				] + $this->variables(), 0, $t);
			}
		}
	}

	/**
	 *
	 * @param Response $response
	 * @return Response
	 * @throws Exception_File_NotFound
	 * @throws Exception_Redirect
	 */
	protected function _execute(Response $response): Response {
		$response->setContent(null);
		$app = $this->router->application;
		$this->_includeFiles();

		$method = $this->options['method'];
		$arguments = $this->args;

		$construct_arguments = $this->_mapVariables($this->optionArray('construct arguments'));
		$method = $this->_mapVariables($method);
		ob_start();

		try {
			if (is_string($method) && str_contains($method, '::')) {
				[$class, $method] = pair($method, '::', 'stdClass', $method);
				$method = new ReflectionMethod($class, $method);
				$object = $method->isStatic() ? null : $app->objects->factoryArguments($class, $construct_arguments);
				$content = $method->invokeArgs($object, $arguments);
			} else {
				$content = call_user_func_array($method, $arguments);
			}
		} catch (Exception_Redirect $e) {
			throw $e;
		} catch (\Exception $e) {
			$content = null;
			$app->hooks->call('exception', $e);
			$app->logger->error('{class}::_execute() Running {method} threw exception {e}', [
				'class' => __CLASS__, 'method' => $app->hooks->callable_string($method), 'e' => $e,
			]);
		}
		if ($content instanceof Response) {
			return $content;
		}
		$buffer = ob_get_clean();
		if ($response->content !== null) {
			return $response;
		}
		if ($response->isJSON()) {
			if ($content !== null) {
				$response->json()->setData($content);
				$response->content = null;
			}
			return $response;
		}
		if (!$this->optionBool('no-buffer')) {
			if ($content === null && !empty($buffer)) {
				$content = $buffer;
			} elseif ($this->optionBool('buffer')) {
				$content = $buffer;
			}
		}
		if (empty($content)) {
			$content = $this->option('empty content', '');
		}
		if ($content !== null) {
			if (is_array($content)) {
				$content = ArrayTools::joinWrap($content, $this->option('join_prefix', ''), $this->option('join_suffix', ''));
			}
			$response->content = $this->option('prefix', '') . $content . $this->option('suffix', '');
		}
		return $response;
	}
}
