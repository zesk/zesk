<?php declare(strict_types=1);

namespace zesk\Cron\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Cron {
	/**
	 * At scheduled time, runs a process on all servers at around the same time
	 */
	public const SCOPE_APPLICATION = 'application';

	/**
	 * At scheduled time, runs a process on a single servers at around the same time
	 */
	public const SCOPE_SERVER = 'server';

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
}
