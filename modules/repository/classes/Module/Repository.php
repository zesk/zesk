<?php
/**
 * 
 */
namespace zesk;

/**
 * @see Repository
 * @author kent
 *
 */
class Module_Repository extends Module {
	/**
	 * 
	 * @var array
	 */
	private $repository_types = array();
	/**
	 * 
	 * @var array
	 */
	private $repository_classes = array();
	
	/**
	 *
	 * @param string $class
	 * @param array $aliases
	 */
	private function _register_repository($class, array $aliases = array()) {
		$this->repository_classes[$class] = $aliases;
		foreach ($aliases as $alias) {
			$this->repository_types[strtolower($alias)] = $class;
		}
		$this->application->classes->register($class);
		return $this;
	}
	
	/**
	 * 
	 * @param string $class
	 * @param array $aliases
	 */
	private function _find_repository($type) {
		$lowtype = strtolower($type);
		if (array_key_exists($lowtype, $this->repository_types)) {
			return $this->repository_types[$lowtype];
		}
		return null;
	}
	
	/**
	 * Return master module
	 * @return self
	 */
	public function singleton() {
		return $this->application->modules->object("Repository");
	}
	/**
	 * 
	 * @param string $class
	 * @param array $aliases
	 * @return Module_Repository
	 */
	public function register_repository($class, array $aliases = array()) {
		return $this->singleton()->_register_repository($class, $aliases);
	}
	
	/**
	 *
	 * @param string $type
	 * @return string|NULL
	 */
	public function find_repository($type) {
		return $this->singleton()->_find_repository($type);
	}
	
	/**
	 * Determine whether a directory can be treated as a repository.
	 * 
	 * @param string $directory
	 * @return Repository[]
	 */
	public function determine_repository($directory) {
		$repos = array();
		foreach ($this->singleton()->repository_classes as $class => $aliases) {
			/* @var $repo Repository */
			$repo = $this->application->factory($class, $this->application);
			if ($repo->validate($directory)) {
				$repos[$repo->code()] = $repo;
			}
		}
		return $repos;
	}
}
