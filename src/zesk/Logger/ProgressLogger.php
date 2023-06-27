<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Logger;

use Psr\Log\LoggerInterface;
use zesk\Interface\ProgressStack;

/**
 *
 * @author kent
 *
 */
class ProgressLogger implements ProgressStack
{
	/**
	 *
	 * @var LoggerInterface
	 */
	private LoggerInterface $logger;

	/**
	 *
	 * @var string
	 */
	private string $level;

	/**
	 *
	 * @param LoggerInterface $logger
	 * @param string $level
	 */
	public function __construct(LoggerInterface $logger, string $level = 'info')
	{
		$this->logger = $logger;
		$this->level = $level;
	}

	public function progress($status = null, $percent = null): void
	{
		$this->logger->log($this->level, '{status} ({percent}%)', [
			'status' => $status,
			'percent' => $percent,
		]);
	}

	public function progressPush($name): void
	{
		$this->logger->log($this->level, 'BEGIN {name} {', [
			'name' => $name,
		]);
	}

	public function progressPop(): void
	{
		$this->logger->log($this->level, '} END');
	}
}
