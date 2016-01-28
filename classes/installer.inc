<?php

abstract class Installer {
	/**
	 * @var array
	 */
	protected $errors = array();
	
	function require_function($function) {
		if (!function_exists($function)) {
			$this->errors[] = "$function is required";
		}
	}
	
	function requirements() {
		return array();
	}
	
	abstract function install();
	
	function uninstall() {
		/* Nada */
	}
}