<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	 * Getter for done state
	 *
	 * @param
	 *        	boolean
	 */
	public function done();

	/**
	 * Kill/interrupt this process.
	 * Harsher than ->terminate();
	 */
	public function kill();

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	public function terminate();

	/**
	 * Take a nap.
	 * I love naps.
	 */
	public function sleep($seconds = 1.0);

	/**
	 * Logging tool for processes
	 *
	 * @param string $message
	 * @param array $args
	 */
	public function log($message, array $args = []);
}
