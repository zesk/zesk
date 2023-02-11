<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * For long processes which can be interrupted.
 */
interface Interface_Process {
	/**
	 * Retrieve current application
	 *
	 * @return Application
	 */
	public function application(): Application;

	/**
	 * Set current application
	 *
	 * @param Application $set
	 * @return $this
	 */
	public function setApplication(Application $set): self;

	/**
	 * Is this process done?
	 */
	public function done(): bool;

	/**
	 * Kill/interrupt this process.
	 * Harsher than ->terminate();
	 */
	public function kill(): void;

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	public function terminate(): void;

	/**
	 * Take a nap.
	 * I love naps.
	 */
	public function sleep(float $seconds = 1.0): void;

	/**
	 * Logging tool for processes
	 *
	 * @param string $message
	 * @param array $args
	 */
	public function log(string $message, array $args = []): void;
}
