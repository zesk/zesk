<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use zesk\Interface\SystemProcess;

/**
 * Mock Process for testing
 *
 * @author kent
 */
class MockProcess extends Hookable implements SystemProcess {
	/**
	 * @var bool
	 */
	private bool $done;

	/**
	 *
	 * @var Timer
	 */
	private Timer $timer;

	/**
	 *
	 * @var integer
	 */
	private int $quit_after;

	/**
	 * Construct our mock process
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->timer = new Timer();
		$this->quit_after = $this->optionInt('quit_after', 60); // 60 seconds should be good, right?
		$this->done = false;
	}

	/**
	 * @return Application
	 */
	public function application(): Application {
		return $this->application;
	}

	/**
	 * @param Application $set
	 * @return $this
	 */
	public function setApplication(Application $set): self {
		$this->application = $set;
		return $this;
	}

	private function _done(): void {
		$this->invokeHooks(SystemProcess::HOOK_DONE, [$this]);
		$this->done = true;
	}

	/**
	 * Getter for done state
	 *
	 * @return bool
	 */
	public function done(): bool {
		if ($this->done) {
			return true;
		}
		if ($this->timer->elapsed() < $this->quit_after) {
			return false;
		}
		$this->_done();
		return true;
	}

	/**
	 * Kill/interrupt this process.
	 * Not nice way to do it.
	 */
	public function kill(): void {
		$this->terminate();
	}

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	public function terminate(): void {
		if (!$this->done) {
			$this->_done();
		}
	}

	/**
	 * Take a nap.
	 * I love naps.
	 */
	public function sleep($seconds = 1.0): void {
		usleep($seconds * 1000000);
	}

	/**
	 * Logging tool for processes
	 *
	 * @param $level
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = []): void {
		$this->application->log($context['severity'] ?? 'info', $message, $context);
	}
}
