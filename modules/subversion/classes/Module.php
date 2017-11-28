<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_Subversion extends Module_Repository {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	function initialize() {
		parent::initialize();
		$this->register_repository("zesk\\Repository_Subversion", array(
			"subversion",
			"svn"
		));
	}
}