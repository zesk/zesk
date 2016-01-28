<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/classes/process/interruptable.inc $
* @package zesk
* @subpackage system
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2008, Market Acumen, Inc.
*/

/**
 * For long processes which can be interrupted.
 */
interface Process_Interruptable {

	/**
	 * Retrieve current application
	 *
	 * @return Application
	 */
	function application(Application $set = null);

	/**
	 * Getter for done state
	 *
	 * @param
	 *        	boolean
	 */
	function done();

	/**
	 * Kill/interrupt this process.
	 * Harsher than ->terminate();
	 *
	 * @param string $interrupt
	 */
	function kill();

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	function terminate();

	/**
	 * Take a nap.
	 * I love naps.
	 */
	function sleep($seconds = 1.0);

	/**
	 * Logging tool for processes
	 *
	 * @param string $message
	 * @param array $args
	 * @param string $level
	 */
	function log($message, array $args = array(), $level = null);
}

