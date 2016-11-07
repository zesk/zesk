<?php
abstract class Command_AWS_Base extends Command_Base {
	
	/**
	 * 
	 * @var AWS_EC2_Awareness
	 */
	protected $awareness = null;
	function initialize() {
		parent::initialize();
		
		$this->awareness = new AWS_EC2_Awareness();
	}
}
