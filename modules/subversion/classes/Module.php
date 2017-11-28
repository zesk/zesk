<?php
/**
 * 
 */
namespace zesk\Subversion;

/**
 * 
 * @author kent
 *
 */
class Module extends \zesk\Module_Repository {
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	function initialize() {
		parent::initialize();
		$this->register_repository(Repository::class, array(
			"svn",
			"subversion"
		));
	}
}