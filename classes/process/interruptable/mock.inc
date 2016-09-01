<?php
/**
 * Mock Process Interruptable for testing
 *
 * @author kent
 */
class Process_Interruptable_Mock extends Hookable implements Process_Interruptable {
	
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
	 *
	 * @var Application
	 */
	private $application = null;
	
	/**
	 * Construct our mock process 
	 * 
	 * @param Application $applicadtion
	 * @param array $options
	 */
	function __construct(Application $applicadtion, $options = null) {
		parent::__construct($options);
		$this->application = $applicadtion;
		$this->timer = new Timer();
		$this->quit_after = $this->option_integer("quit_after", 60); // 60 seconds should be good, right?
	}
	
	/**
	 * Getter/setter for application
	 * 
	 * {@inheritDoc}
	 * @see Process_Interruptable::application()
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
	 * @param boolean
	 */
	function done() {
		if ($this->timer->elapsed() > $this->quit_after) {
			return true;
		}
		return $this->hook_array('done', array(), $this->done);
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
	function log($message, array $args = array(), $level = null) {
		log::send($message, $args, $level);
	}
}