<?php
declare(strict_types=1);

namespace zesk\Daemon\Attributes;

use Attribute;
use ReflectionMethod;
use zesk\HookableAttribute;

#[Attribute(flags: Attribute::TARGET_METHOD)]
class DaemonMethod implements HookableAttribute {
	/**
	 * Number of problems you may have
	 *
	 * @var int
	 */
	public const MAX_PROCESS_COUNT = 99;

	/**
	 * Daemon method
	 *
	 * @var ReflectionMethod
	 */
	private ReflectionMethod $method;

	/**
	 * @var int
	 */
	private int $processCount;

	public function __construct(int $processCount = 1) {
		$this->processCount = $processCount <= 0 ? 1 : (($processCount > self::MAX_PROCESS_COUNT) ? self::MAX_PROCESS_COUNT : $processCount);
	}

	public function setMethod(ReflectionMethod $method): self {
		$this->method = $method;
		return $this;
	}

	public function id(): string {
		return $this->method->getName();
	}

	/**
	 * Guaranteed to be greater than zero
	 * @return int
	 */
	public function processCount(): int {
		return $this->processCount;
	}

	/**
	 * @param array $arguments
	 * @return mixed
	 */
	public function run(array $arguments = []): mixed {
		$this->method->run(null, $arguments);
		return null;
	}
}
