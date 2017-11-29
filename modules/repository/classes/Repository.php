<?php
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see \zesk\Repository_Command
 * @see \zesk\Subversion\Repository
 * @see \zesk\Git\Repository
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
	 * @var string
	 */
	protected $path = null;
	
	/**
	 * 
	 * @param Application $application
	 * @param string $root Path to repository root directory or a file within the repository
	 * @param array $options
	 */
	final public function __construct(Application $application, $root = null, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options();
		if (is_string($root)) {
			$this->set_path($root);
		}
		$this->initialize();
	}
	
	/**
	 * 
	 * @param string $suffix
	 * @return string
	 */
	public function path($suffix = null) {
		if (!$this->path) {
			throw new Exception_Semantics("Need to set the path before using path call");
		}
		return path($this->path, $suffix);
	}
	/**
	 * @param string $path
	 * @return \zesk\Repository
	 */
	public function set_path($path) {
		$this->path = $path;
		return $this;
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
	abstract public function validate();
	
	/**
	 * Fetch a list of repository status for a target
	 *
	 * @param string $target
	 * @param boolean $updates
	 *
	 * @return array[]
	 */
	abstract public function status($target = null, $updates = false);
	
	/**
	 * Synchronizes all files beneath $target with repository.
	 * 
	 * @param string $target
	 * @param string $message
	 */
	abstract public function commit($target = null, $message = null);
	
	/**
	 * Update repository target at target, and get changes from remote
	 * 
	 * @param string $target
	 */
	abstract public function update($target = null);
	
	/**
	 * Check a target prior to updating it
	 *
	 * @param string $target
	 * @return boolean True if update should continue
	 */
	abstract public function pre_update($target = null);
	
	/**
	 * Undo changes to a target and reset to current branch/tag
	 * 
	 * @param string $target Directory of target directory
	 * @return boolean
	 */
	abstract public function rollback($target = null);
	
	/**
	 * Run before target is updated with new data
	 * 
	 * @param string $target
	 * @return boolean
	 */
	abstract public function post_update($target = null);
	
	/**
	 * Return the latest version string for this repository. Should mimic `zesk version` formatting.
	 * 
	 * @return string
	 */
	abstract public function latest_version();
}
