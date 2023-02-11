<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 * Mock Process for testing
 *
 * @author kent
 */
class MockProcess extends Hookable implements Interface_Process {
	/**
	 * Done yet?
	 *
	 * @var boolean
	 */
	private bool $done = false;

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

	/**
	 * Getter for done state
	 *
	 * @param
	 *            boolean
	 */
	public function done(): bool {
		if ($this->timer->elapsed() > $this->quit_after) {
			return true;
		}
		return $this->callHookArguments('done', [], $this->done);
	}

	/**
	 * Kill/interrupt this process.
	 * Harsher than ->done(true);
	 */
	public function kill(): void {
		$this->done = true;
	}

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	public function terminate(): void {
		$this->done = true;
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
	 * @param string $message
	 * @param array $args
	 */
	public function log(string $message, array $args = []): void {
		$this->application->logger->log($args['severity'] ?? 'info', $message, $args);
	}
}
