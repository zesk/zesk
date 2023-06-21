<?php
declare(strict_types=1);

namespace zesk;

use Attribute;
use Closure;
use ReflectionMethod;
use ReflectionException;
use zesk\Application\Hooks;
use zesk\Exception\ParameterException;

/**
 * @see Hookable
 * @see Hooks
 */
#[Attribute(flags: Attribute::TARGET_METHOD)]
class HookMethod implements HookableAttribute {
	public Closure $method;

	public string $name = '';

	/**
	 * @var array
	 */
	public array $argumentTypes;

	/**
	 * @var bool
	 */
	private bool $filter;

	public function __construct(string|array $handles, array $argumentTypes = [], bool $filter = false) {
		$this->handles = ArrayTools::valuesFlipCopy(Types::toList($handles));
		$this->argumentTypes = $argumentTypes;
		$this->filter = $filter;
	}

	public array $handles;

	public Hookable|null $object;

	public function setClosure(Closure $closure, string $name = ''): self {
		$this->method = $closure;
		if ($name) {
			$this->name = $name;
		} else {
			$this->name = '';
		}
		return $this;
	}

	public function setMethod(ReflectionMethod $method): self {
		$this->method = fn (array $arguments = []) => $method->invokeArgs($this, $arguments);
		$this->name = $method->getName();
		return $this;
	}

	/**
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * @param Hookable|null $object
	 * @return $this
	 */
	public function setObject(Hookable|null $object): self {
		$this->object = $object;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isFilter(): bool {
		return $this->filter;
	}

	public function handlesHook(string $name): bool {
		return array_key_exists($name, $this->handles);
	}

	/**
	 * @param array $arguments
	 * @return mixed
	 * @throws ParameterException
	 * @throws ReflectionException
	 */
	public function run(array $arguments = []): mixed {
		foreach ($this->argumentTypes as $index => $argumentType) {
			if (!array_key_exists($index, $arguments)) {
				throw new ParameterException("Require argument of type $argumentType at position $index");
			}
			$givenType = Types::type($arguments[$index]);
			if ($givenType !== $argumentType) {
				throw new ParameterException("Need argument of type $argumentType for argument $index, $givenType given");
			}
		}
		return $this->method->call($this->object, $arguments);
	}
}
