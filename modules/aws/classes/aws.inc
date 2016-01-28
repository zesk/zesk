<?php

/**
 * Base class for inheriting options across all AWS classes
 * 
 * @author kent
 */
class AWS extends Options {
	function __construct($options = null) {
		parent::__construct($options);
		$this->inherit_global_options();
	}
}