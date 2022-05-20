<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Progress_Logger implements Interface_Progress {
	/**
	 *
	 * @var Logger
	 */
	private $logger = null;

	/**
	 *
	 * @var string
	 */
	private $level = null;

	/**
	 *
	 * @param Logger $logger
	 * @param string $level
	 */
	public function __construct(Logger $logger, $level = 'info') {
		$this->logger = $logger;
		$this->level = $level;
	}

	public function progress($status = null, $percent = null): void {
		$this->logger->log($this->level, '{status} ({percent}%)', [
			'status' => $status,
			'percent' => $percent,
		]);
	}

	public function progress_push($name): void {
		$this->logger->log($this->level, 'BEGIN {name} {', [
			'name' => $name,
		]);
	}

	public function progress_pop(): void {
		$this->logger->log($this->level, '} END');
	}
}
