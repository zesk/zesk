<?php
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
abstract class Repository extends Hookable {
	
	/**
	 * Override in subclasses
	 * 
	 * @var string
	 */
	protected $code = null;
	/**
	 * 
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		parent::__construct($application);
		$this->inherit_global_options();
		$this->initialize();
	}
	
	/**
	 * 
	 */
	protected function initialize() {
	}
	
	/**
	 * Code name for this repository
	 * 
	 * @return string
	 */
	public final function code() {
		return $this->code;
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
	 * Check if the directory is a valid directory for this repository
	 * 
	 * @param string $directory
	 * @return boolean
	 */
	abstract public function validate($directory);
	
	/**
	 * Fetch a list of repository status for a target
	 *
	 * @param string $target
	 * @param boolean $updates
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
