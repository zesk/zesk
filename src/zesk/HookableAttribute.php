<?php declare(strict_types=1);

namespace zesk;

use ReflectionMethod;

/**
 *
 */
interface HookableAttribute
{
	public function setMethod(ReflectionMethod $method): self;

	public function run(array $arguments = []): mixed;
}
