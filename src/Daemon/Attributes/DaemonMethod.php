<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Daemon\Attributes;

use Attribute;
use ReflectionException;
use ReflectionMethod;
use zesk\Daemon\ConfigurationManager;
use zesk\Hookable;
use zesk\HookableAttribute;

#[Attribute(flags: Attribute::TARGET_METHOD)]
class DaemonMethod implements HookableAttribute
{
	/**
	 * Daemon method
	 *
	 * @var ReflectionMethod
	 */
	private ReflectionMethod $method;

	private Hookable|null $object = null;

	/**
	 * @var int
	 */
	private string $managerClass;

	public function __construct(string $managerClass = ConfigurationManager::class)
	{
		$this->$managerClass = $managerClass;
	}

	public function setMethod(ReflectionMethod $method, Hookable|null $object = null): self
	{
		$this->method = $method;
		$this->object = $object;
		return $this;
	}

	public function id(): string
	{
		return $this->method->getName();
	}

	/**
	 * @return string
	 */
	public function managerClass(): string
	{
		return $this->managerClass;
	}

	/**
	 * @param array $arguments
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function run(array $arguments = []): mixed
	{
		$this->method->getClosure()->call($this->object, $arguments);
		return null;
	}
}
