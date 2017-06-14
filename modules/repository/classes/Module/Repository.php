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
class Module_Repository extends Module {
	/**
	 * 
	 * @var array
	 */
	private static $repository_types = array();
	/**
	 * 
	 * @var array
	 */
	private static $repository_classes = array();
	
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
	 * 
	 * @param string $class
	 * @param array $aliases
	 * @return Module_Repository
	 */
	public function register_repository($class, array $aliases = array()) {
		return $this->application->modules->object("Repository")->_register_repository($class, $aliases);
	}
	
	/**
	 *
	 * @param string $type
	 * @return string|NULL
	 */
	public function find_repository($type) {
		return $this->application->modules->object("Repository")->_find_repository($type);
	}
}
