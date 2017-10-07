<?php
/**
 * 
 */
namespace zesk\SMS;

abstract class Module extends \zesk\Module {
	function initialize() {
		$this->application->register_class('zesk\\SMS\\Mail\\Message');
	}
}