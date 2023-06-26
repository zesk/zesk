<?php
declare(strict_types=1);

namespace zesk\Daemon\Attributes;

use Attribute;
use ReflectionMethod;
use zesk\HookableAttribute;
use zesk\Daemon\ConfigurationManager;

#[Attribute(flags: Attribute::TARGET_METHOD)]
class DaemonMethod implements HookableAttribute {
	/**
	 * Daemon method
	 *
	 * @var ReflectionMethod
	 */
	private ReflectionMethod $method;

	/**
	 * @var int
	 */
	private string $managerClass;

	public function __construct(string $managerClass = ConfigurationManager::class) {
		$this->$managerClass = $managerClass;
	}

	public function setMethod(ReflectionMethod $method): self {
		$this->method = $method;
		return $this;
	}

	public function id(): string {
		return $this->method->getName();
	}

	/**
	 * @return string
	 */
	public function managerClass(): string {
		return $this->managerClass;
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
