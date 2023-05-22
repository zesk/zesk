<?php
declare(strict_types=1);

namespace zesk\Cron\Attributes;

use Attribute;
use ReflectionException;
use ReflectionMethod;
use zesk\Hookable;
use zesk\HookableAttribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Cron implements HookableAttribute {
	/**
	 * At scheduled time, run a process on every instance of the application. (Everywhere)
	 */
	public const SCOPE_APPLICATION = 'application';

	/**
	 * At scheduled time, runs a process, one per SERVER in the cluster (usually per file system)
	 */
	public const SCOPE_SERVER = 'server';

	/**
	 * At scheduled time, runs on one and only ONE process in the cluster
	 */
	public const SCOPE_CLUSTER = 'cluster';

	/**
	 * @param string $schedule
	 * @param string $scope
	 * @psalm-param self::SCOPE_SERVER|self::SCOPE_APPLICATION $scope
	 */
	public function __construct(string $schedule = '*', string $scope = self::SCOPE_APPLICATION) {
		$this->schedule = $schedule;
		$this->scope = $scope;
	}

	/**
	 * @var string
	 */
	public string $schedule;

	/**
	 * @var string
	 */
	public string $scope;

	/**
	 * @var ReflectionMethod
	 */
	protected ReflectionMethod $method;

	/**
	 * @param ReflectionMethod $method
	 * @return $this
	 */
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
	public function run(null|Hookable $object, array $arguments = []): mixed {
		return $this->method->invokeArgs($object, $arguments);
	}
}
