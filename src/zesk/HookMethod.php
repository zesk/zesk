<?php
declare(strict_types=1);

namespace zesk;

use Attribute;
use ReflectionMethod;
use ReflectionException;

#[Attribute(flags: Attribute::TARGET_METHOD)]
class HookMethod implements HookableAttribute {
	public ReflectionMethod $method;

	/**
	 * @var array
	 */
	public array $argumentTypes;

	public function __construct(string $handles, array $argumentTypes = []) {
		$this->handles = $handles;
		$this->argumentTypes = $argumentTypes;
	}

	public string $handles;

	public function setMethod(ReflectionMethod $method): self {
		$this->method = $method;
		return $this;
	}

	/**
	 * @param Hookable|null $object
	 * @param array $arguments
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function run(Hookable|null $object, array $arguments = []): mixed {
		foreach ($this->argumentTypes as $index => $argumentType) {
			$givenType = Types::type($arguments[$index]);
			if ($givenType !== $argumentType) {
				throw new ReflectionException("Need argument of type $argumentType for argument $index, $givenType given");
			}
		}
		return $this->method->invokeArgs($object, $arguments);
	}
}
