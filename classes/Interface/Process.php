<?php
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
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
    public function application(Application $set = null);

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
     *
     * @param string $interrupt
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
    public function log($message, array $args = array());
}
