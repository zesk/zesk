<?php
/**
 *
 */
namespace zesk;

/**
 * Registry for repositories and aliases.
 *
 * @see Repository
 * @author kent
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
	 * Singleton function
	 * @param unknown $directory
	 * @return \zesk\Repository[]
	 */
	protected function _determine_repositories($directory) {
		$repos = array();
		foreach ($this->repository_classes as $class => $aliases) {
			/* @var $repo Repository */
			$repo = $this->application->factory($class, $this->application, $directory);
			if ($repo->validate()) {
				$repos[$repo->code()] = $repo;
			}
		}
		return $repos;
	}
	/**
	 * Determine whether a directory can be treated as a repository.
	 *
	 * @param string $directory
	 * @return Repository[]
	 */
	public function determine_repository($directory) {
		return $this->singleton()->_determine_repositories();
	}
	
	/**
	 *
	 * @param string $directory
	 */
	public function factory($directory) {
		$repos = $this->determine_repository($directory);
		if (count($repos) > 1) {
			$this->application->logger->warning("{method} multiple repositories detected ({repos}), using first {repo}", array(
				"method" => __METHOD__,
				"repos" => array_keys($repos),
				"repo" => first(array_keys($repos))
			));
			return first($repos);
		}
		if (count($repos) > 0) {
			return first($repos);
		}
		throw new Exception_NotFound("No repository marker found at {directory}", array(
			"directory" => $directory
		));
	}
}
