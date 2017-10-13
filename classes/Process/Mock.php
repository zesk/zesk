<?php

/**
 *
 */
namespace zesk;

/**
 * Mock Process for testing
 *
 * @author kent
 */
class Process_Mock extends Hookable implements Interface_Process {
	
	/**
	 * Done yet?
	 *
	 * @var boolean
	 */
	private $done = false;
	
	/**
	 *
	 * @var Timer
	 */
	private $timer = null;
	
	/**
	 *
	 * @var integer
	 */
	private $quit_after = null;
	
	/**
	 * Construct our mock process
	 *
	 * @param Application $application
	 * @param array $options
	 */
	function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->timer = new Timer();
		$this->quit_after = $this->option_integer("quit_after", 60); // 60 seconds should be good, right?
	}
	
	/**
	 * Getter/setter for application
	 *
	 * {@inheritdoc}
	 *
	 * @see zesk\Interface_Process::application()
	 */
	function application(Application $set = null) {
		if ($set) {
			$this->application = $set;
			return $this;
		}
		return $this->application;
	}
	/**
	 * Getter for done state
	 *
	 * @param
	 *        	boolean
	 */
	function done() {
		if ($this->timer->elapsed() > $this->quit_after) {
			return true;
		}
		return $this->call_hook_arguments('done', array(), $this->done);
	}
	
	/**
	 * Kill/interrupt this process.
	 * Harsher than ->done(true);
	 *
	 * @param string $interrupt
	 */
	function kill() {
		$this->done = true;
	}
	
	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	function terminate() {
		$this->done = true;
	}
	
	/**
	 * Take a nap.
	 * I love naps.
	 */
	function sleep($seconds = 1.0) {
		usleep($seconds * 1000000);
	}
	
	/**
	 * Logging tool for processes
	 *
	 * @param string $message
	 * @param array $args
	 * @param string $level
	 */
	function log($message, array $args = array()) {
		$this->application->logger->log(avalue($args, 'severity', 'info'), $message, $args);
	}
}
