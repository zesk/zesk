<?php
declare(strict_types=1);

namespace zesk\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

class InterfaceAdapter implements LoggerInterface {
	use LoggerTrait;

	protected LoggerInterface $interface;

	public function __construct(LoggerInterface $interface) {
		$this->interface = $interface;
	}

	/**
	 * @param $level
	 * @param Stringable|string $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, Stringable|string $message, array $context = []): void {
		$this->interface->log($level, $message, $context);
	}
}
