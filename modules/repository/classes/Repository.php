<?php
namespace zesk;

abstract class Repository extends Hookable {
	
	/**
	 * 
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		parent::__construct($application);
		$this->initialize();
	}
	
	/**
	 * 
	 */
	protected function initialize() {
	}
	
	/**
	 * 
	 * @param Application $application
	 * @param unknown $type
	 * @return NULL|Repository
	 */
	public static function factory(Application $application, $type) {
		try {
			$repo = $application->modules->object("Repository");
			/* @var $repo Module_Repository */
			$class = $repo->find_repository($type);
			if (!$class) {
				return null;
			}
			return $application->objects->factory($class, $application);
		} catch (Exception_Class_NotFound $e) {
			return null;
		}
	}
	
	/**
	 * Fetch a list of repository status for a target
	 * 
	 * @param unknown $target
	 * @param string $updates
	 * 
	 * @return array[]
	 */
	abstract public function status($target, $updates = false);
	
	/**
	 * Synchronizes all files beneath $target with repository.
	 * 
	 * @param string $target
	 * @param string $message
	 */
	abstract public function commit($target, $message = null);
	
	/**
	 * Update repository target at target, and get changes from remote
	 * 
	 * @param string $target
	 */
	abstract public function update($target);
	
	/**
	 * Check a target prior to updating it
	 *
	 * @param string $target
	 * @return boolean True if update should continue
	 */
	abstract public function pre_update($target);
	
	/**
	 * Undo changes to a target and reset to current branch/tag
	 * 
	 * @param string $target Directory of target directory
	 * @return boolean
	 */
	abstract public function rollback($target);
	
	/**
	 * Run before target is updated with new data
	 * 
	 * @param string $target
	 * @return boolean
	 */
	abstract public function post_update($target);
}
