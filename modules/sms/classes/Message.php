<?php
/**
 * 
 */
namespace zesk\SMS;

use zesk\Hookable;
use zesk\Application;

abstract class Message extends Hookable {
	
	/**
	 * Construct an SMS Message to send.
	 * 
	 * Add an initialize hook to subclasses:
	 * 
	 * 		protected function hook_construct() {
	 *      }
	 *      
	 * To add internal initialization code for object construction.
	 * 
	 * @param Application $application
	 * @param array $options
	 */
	final function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options();
		$this->call_hook("construct");
	}
	
	/**
	 * Send the SMS message
	 */
	abstract function send();
}