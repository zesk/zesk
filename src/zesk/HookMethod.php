<?php
declare(strict_types=1);

namespace zesk;

use Attribute;
use Closure;
use ReflectionMethod;
use zesk\Application\Hooks;
use zesk\Exception\ParameterException;

/**
 * @see Hookable
 * @see Hooks
 */
#[Attribute(flags: Attribute::TARGET_METHOD)]
class HookMethod implements HookableAttribute
{
	/**
	 * @var Closure
	 */
	public Closure $method;

	/**
	 * @var string
	 */
	public string $name = '';

	/**
	 * @var array
	 */
	public array $argumentTypes;

	/**
	 * @var bool
	 */
	private bool $filter;

	/**
	 * @var array
	 */
	public array $handles;

	/**
	 * @var Hookable|null
	 */
	public Hookable|null $object;

	/**
	 * @param string|array $handles List of hook names this method handles
	 * @param array $argumentTypes Optional argument types for type-checking
	 * @param object|null $object Object to use
	 * @param bool $filter True when this hook is used as a filter and should have a compatible method signatures of
	 * other filters of this hook name.
	 */
	public function __construct(string|array $handles, array $argumentTypes = [], object $object = null, bool $filter = false)
	{
		$this->handles = ArrayTools::valuesFlipCopy(Types::toList($handles));
		$this->argumentTypes = $argumentTypes;
		$this->filter = $filter;
		$this->object = $object;
	}

	/**
	 * Manually set the method closure for this hook method
	 *
	 * @param Closure $closure
	 * @param string $name
	 * @return $this
	 */
	public function setClosure(Closure $closure, string $name = ''): self
	{
		$this->method = $closure;
		if ($name) {
			$this->name = $name;
		} else {
			$this->name = '';
		}
		return $this;
	}

	/**
	 * Set the reflection method for this hook method
	 *
	 * @param ReflectionMethod $method
	 * @return $this
	 */
	public function setMethod(ReflectionMethod $method): self
	{
		$this->method = fn () => $method->invokeArgs($this->object, func_get_args());
		$this->name = $method->getName();
		return $this;
	}

	/**
	 * Return a string name for this hook
	 *
	 * @return string
	 */
	public function name(): string
	{
		return $this->name;
	}

	/**
	 * Set the object used when invoking this hook. Is null for static methods.
	 *
	 * @param Hookable|null $object
	 * @return $this
	 */
	public function setObject(Hookable|null $object): self
	{
		$this->object = $object;
		return $this;
	}

	/**
	 * Is this a filter?
	 *
	 * @return bool
	 */
	public function isFilter(): bool
	{
		return $this->filter;
	}

	/**
	 * Does this handle hook $name?
	 *
	 * @param string $name
	 * @return bool
	 */
	public function handlesHook(string $name): bool
	{
		return array_key_exists($name, $this->handles);
	}

	/**
	 * Run a hook and return the result
	 *
	 * @param array $arguments
	 * @return mixed
	 * @throws ParameterException
	 */
	public function run(array $arguments = []): mixed
	{
		foreach ($this->argumentTypes as $index => $argumentType) {
			if (!array_key_exists($index, $arguments)) {
				throw new ParameterException("Require argument of type $argumentType at position $index");
			}
			$arg = $arguments[$index];
			$givenType = Types::type($arg);
			if ((is_object($arg) && !is_subclass_of($arg, $argumentType)) || ($givenType !== $argumentType)) {
				throw new ParameterException("Need argument of type $argumentType for argument $index, $givenType given");
			}
		}
		if ($this->object) {
			$this->method->bindTo($this->object);
		}
		return call_user_func_array($this->method, $arguments);
	}
}
