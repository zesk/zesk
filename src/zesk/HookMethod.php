<?php
declare(strict_types=1);

namespace zesk;

use Attribute;
use ReflectionMethod;
use ReflectionException;
use zesk\Exception\ParameterException;

#[Attribute(flags: Attribute::TARGET_METHOD)]
class HookMethod implements HookableAttribute {
	public ReflectionMethod $method;

	/**
	 * @var array
	 */
	public array $argumentTypes;

	public function __construct(string|array $handles, array $argumentTypes = []) {
		$this->handles = ArrayTools::valuesFlipCopy(Types::toList($handles));
		$this->argumentTypes = $argumentTypes;
	}

	public array $handles;

	public Hookable|null $object;

	public function setMethod(ReflectionMethod $method): self {
		$this->method = $method;
		return $this;
	}

	/**
	 * @return string
	 */
	public function name(): string {
		return $this->method->getName();
	}

	/**
	 * @param Hookable|null $object
	 * @return $this
	 */
	public function setObject(Hookable|null $object): self {
		$this->object = $object;
		return $this;
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
		return $this->method->invokeArgs($this->object, $arguments);
	}
}
